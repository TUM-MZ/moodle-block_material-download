<?php
// Copyright (c) 2013 onwards Paola Frignani, TH Ingolstadt

// Moodle Download Plugin is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.

// Verpackt die Dateien und Verzeichnisse eines Kurses in einer .zip-Datei und sendet sie dem Browser zum Downloaden

require_once ('../../config.php');
require_once ($CFG->dirroot . '/lib/filelib.php');
require_once ($CFG->dirroot . '/lib/moodlelib.php');

$courseid = required_param('courseid', PARAM_INT);
$ccsectid = required_param('ccsectid', PARAM_INT);
$course   = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
$context  = context_course::instance($courseid);

$PAGE->set_url('/mod/course/view.php', array('id' => $courseid));
$user = $USER;

$fs       = get_file_storage();
$zipper   = get_file_packer('application/zip');
$filename = str_replace(' ', '_', clean_filename($course->shortname."-".date("Ymd"))); //name of new zip file.

$ersetzen_mit = array('Ä', 'ä', 'Ö', 'ö', 'Ü', 'ü', 'ß', ' ', '/');
$ersetzt = array('Ae', 'ae', 'Oe', 'oe', 'Ue', 'ue', 'ss', '_', '-');

$resources['resource'] = get_string('dm_resource', 'block_material_download');
$resources['folder']   = get_string('dm_folder',   'block_material_download');

$modinfo     = get_fast_modinfo($course);
$cms         = array();
$materialien = array();
$files_zum_downloaden = array();

foreach ($modinfo->instances as $modname=>$instances) {
    if(array_key_exists($modname, $resources)) {
        foreach($instances as $instances_id=>$instance) {
            if (!$instance->uservisible) {
                continue;
            }
            $cms[$instance->id] = $instance;
            $materialien[$instance->modname][] = $instance->id;
        }
    }
}

if($course->format == "topics") { $subfolder = get_string('dm_topic', 'block_material_download'); }
if($course->format == "weeks")  { $subfolder = get_string('dm_week',  'block_material_download'); }

if ($ccsectid != 0 && !empty($ccsectid)) {
    $filename = $filename . "_" . $subfolder . "_" . $ccsectid;
} else {
    $filename = $filename;
}

foreach ($materialien as $material_name => $single_material) {
    $anzahl = count($single_material);
    for ($ii=0; $ii<$anzahl; $ii++) {
        $material_infos = $cms[$single_material[$ii]];

        $tmp_files=$fs->get_area_files($material_infos->context->id, 'mod_'.$material_name, 'content', false, 'sortorder DESC', false);
//            foreach($tmp_files as $t)
//                print_r($t->get_filename() );


        // Dozenten dürfen alle Dateien herunterladen
        reset($tmp_files);

        $tmp_file  = current($tmp_files);

        // Chong 20141119
        $filanamecc = $tmp_file->get_filename();
        $sect_id = $material_infos->sectionnum;


        if ($ccsectid == 0) {
            $temp_size = sizeof($files_zum_downloaden);
            if ($sect_id!=0) {
                $directory = $subfolder.'_'.$sect_id.'/';
            } else {
                $directory = "";
            }
            $temp_file_name =str_replace($ersetzen_mit, $ersetzt, clean_filename($material_infos->name));
            $temp_extension = pathinfo(str_replace($ersetzen_mit, $ersetzt, clean_filename($tmp_file->get_filename())),PATHINFO_EXTENSION);
            if ($temp_extension) {
                $temp_extension = '.'.$temp_extension;
            }
            $files_zum_downloaden[$filename.'/'.$directory.$temp_file_name.$temp_extension] = $tmp_file;
            for($duplicate_count = 1; sizeof($files_zum_downloaden) == $temp_size; $duplicate_count++)
            {
                $files_zum_downloaden[$filename.'/'.$directory.$temp_file_name.' ('.$duplicate_count.')'.$temp_extension] = $tmp_file;
            }
        } else {
            if ($ccsectid == $sect_id) {
                $temp_size = sizeof($files_zum_downloaden);
                $files_zum_downloaden[$filename.'/'.str_replace($ersetzen_mit, $ersetzt, clean_filename($tmp_file->get_filename()))] = $tmp_file;
                for($duplicate_count = 1; sizeof($files_zum_downloaden) == $temp_size; $duplicate_count++)
                {
                    $temp_file_name =pathinfo(str_replace($ersetzen_mit, $ersetzt, clean_filename($tmp_file->get_filename())),PATHINFO_FILENAME);
                    $temp_extension = pathinfo(str_replace($ersetzen_mit, $ersetzt, clean_filename($tmp_file->get_filename())),PATHINFO_EXTENSION);
                    if($temp_extension)
                        $files_zum_downloaden[$filename.'/'.$temp_file_name.' ('.$duplicate_count.').'.$temp_extension] = $tmp_file;
                    else
                        $files_zum_downloaden[$filename.'/'.$temp_file_name.' ('.$duplicate_count.')'] = $tmp_file;
                }
            }
        }
        // Chong 20141119
    }

}
//zip files
$tempzip = tempnam($CFG->tempdir.'/', get_string('dm_materials','block_material_download').'_'.$course->shortname);
$zipper = new zip_packer();
$filename = $filename . ".zip";
if ($zipper->archive_to_pathname($files_zum_downloaden, $tempzip)) {
    send_temp_file($tempzip, $filename);
}

