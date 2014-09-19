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
$course   = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
$context  = context_course::instance($courseid);

$PAGE->set_url('/mod/course/view.php', array('id' => $courseid));
$user = $USER;

$fs       = get_file_storage();
$zipper   = get_file_packer('application/zip');
$filename = str_replace(' ', '_', clean_filename($course->id."-".$course->shortname."-".date("Ymd").".zip")); //name of new zip file.

$ersetzen_mit = array('Ä', 'ä', 'Ö', 'ö', 'Ü', 'ü', 'ß', ' ', '/');
$ersetzt = array('Ae', 'ae', 'Oe', 'oe', 'Ue', 'ue', 'ss', '_', '-');

$resources['resource'] = get_string('dm_resource', 'block_material_download');
$resources['folder']   = get_string('dm_folder',   'block_material_download');

$modinfo     = get_fast_modinfo($course);
$cms         = array();
$materialien = array();

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

// Chong 20140708
$mysqlhost = $CFG->dbhost; // MySQL-Host angeben
$mysqluser = $CFG->dbuser; // MySQL-User angeben
$mysqlpwd  = $CFG->dbpass; // Passwort angeben
$mysqldb   = $CFG->dbname; // Gewuenschte Datenbank angeben
$connect   = mysql_connect($mysqlhost, $mysqluser, $mysqlpwd) or die ("MySQL-Verbindung fehlgeschlagen!");
mysql_select_db($mysqldb, $connect) or die("Konnte die Datenbank nicht waehlen.");
// Chong 20140708

foreach ($materialien as $material_name => $einzelnen_materialien) {
    $anzahl = count($einzelnen_materialien);
    for ($ii=0; $ii<$anzahl; $ii++) {
        $material_infos = $cms[$einzelnen_materialien[$ii]];

        if($material_name == 'resource') {
            $tmp_files=$fs->get_area_files($material_infos->context->id, 'mod_'.$material_name, 'content', false, 'sortorder DESC', false);

            if(count($tmp_files) > 1 && $material_name == 'resource' && !has_capability('moodle/course:viewhiddenactivities', $context)) {
                // Nur die Hauptdatei zippen 
                reset($tmp_files);

                $tmp_file  = current($tmp_files);

                // Chong 20140708
                $filanamecc = $tmp_file->get_filename();

                $sql_chk   = "SELECT `mdl_context`.`instanceid` FROM `mdl_files`, `mdl_context` WHERE `mdl_files`.`filename` = '".$filanamecc."' AND `mdl_files`.`component` = 'mod_resource' AND `mdl_files`.`contextid` = `mdl_context`.`id` AND `mdl_context`.`contextlevel` = '70'";
                $rlt_chk   = mysql_query($sql_chk);
                while ($row_chk = mysql_fetch_array($rlt_chk)){
                    $checkid   = $row_chk['instanceid'];
                    $sql_sec   = "SELECT `mdl_course_sections`.`section` FROM `mdl_course_sections` WHERE `mdl_course_sections`.`course` = '".$course->id."' AND ( `mdl_course_sections`.`sequence` LIKE '".$checkid.",%' OR `mdl_course_sections`.`sequence` LIKE '%,".$checkid.",%' OR `mdl_course_sections`.`sequence` LIKE '%,".$checkid."' OR `mdl_course_sections`.`sequence` = '".$checkid."' ) LIMIT 1";
                    $rlt_sec   = mysql_query($sql_sec);
                    $row_sec   = mysql_fetch_array($rlt_sec);
                    if(!empty($row_sec['section'])) {
	                    $sect_id   = $row_sec['section'];
                    }
                }

                $files_zum_downloaden[$filename.'/'.$subfolder.'_'.$sect_id.'/'.str_replace($ersetzen_mit, $ersetzt, clean_filename($tmp_file->get_filename()))] = $tmp_file;
                // Chong 20140708

            } else {
                // Dozenten dürfen alle Dateien herunterladen            
                foreach($tmp_files as $tmp_file) {

                    // Chong 20140708
					$filanamecc = $tmp_file->get_filename();

                    $sql_chk   = "SELECT `mdl_context`.`instanceid` FROM `mdl_files`, `mdl_context` WHERE `mdl_files`.`filename` = '".$filanamecc."' AND `mdl_files`.`component` = 'mod_resource' AND `mdl_files`.`contextid` = `mdl_context`.`id` AND `mdl_context`.`contextlevel` = '70'";
                    $rlt_chk   = mysql_query($sql_chk);
                    while ($row_chk = mysql_fetch_array($rlt_chk)){
                        $checkid   = $row_chk['instanceid'];
                        $sql_sec   = "SELECT `mdl_course_sections`.`section` FROM `mdl_course_sections` WHERE `mdl_course_sections`.`course` = '".$course->id."' AND ( `mdl_course_sections`.`sequence` LIKE '".$checkid.",%' OR `mdl_course_sections`.`sequence` LIKE '%,".$checkid.",%' OR `mdl_course_sections`.`sequence` LIKE '%,".$checkid."' OR `mdl_course_sections`.`sequence` = '".$checkid."' ) LIMIT 1";
                        $rlt_sec   = mysql_query($sql_sec);
                        $row_sec   = mysql_fetch_array($rlt_sec);
                        if(!empty($row_sec['section'])) {
	                        $sect_id   = $row_sec['section'];
                        }
                    }

                    $files_zum_downloaden[$filename.'/'.$subfolder.'_'.$sect_id.'/'.str_replace($ersetzen_mit, $ersetzt, clean_filename($tmp_file->get_filename()))] = $tmp_file;
                    // Chong 20140708
                }
            }

        } else {
            if(!$tmp_files=$fs->get_file($material_infos->context->id, 'mod_'.$material_name, 'content', '0', '/', '.')) {
                $tmp_files = null;
            }

            // Chong 20140708
            $ordnercc  = $material_infos->name;

            $sql_chk   = "SELECT `mdl_course_modules`.`id` FROM `mdl_folder`, `mdl_course_modules` WHERE `mdl_folder`.`name` = '".$ordnercc."' AND `mdl_folder`.`course` = '".$course->id."' AND `mdl_folder`.`id` = `mdl_course_modules`.`instance`";
            $rlt_chk   = mysql_query($sql_chk);
            while ($row_chk = mysql_fetch_array($rlt_chk)){
                $checkid   = $row_chk['id'];
                $sql_sec   = "SELECT `mdl_course_sections`.`section` FROM `mdl_course_sections` WHERE `mdl_course_sections`.`course` = '".$course->id."' AND ( `mdl_course_sections`.`sequence` LIKE '".$checkid.",%' OR `mdl_course_sections`.`sequence` LIKE '%,".$checkid.",%' OR `mdl_course_sections`.`sequence` LIKE '%,".$checkid."' OR `mdl_course_sections`.`sequence` = '".$checkid."' ) LIMIT 1";
                $rlt_sec   = mysql_query($sql_sec);
                $row_sec   = mysql_fetch_array($rlt_sec);
                if(!empty($row_sec['section'])) {
	                $sect_id   = $row_sec['section'];
                }
            }
            // Chong 20140708

            $files_zum_downloaden[$filename.'/'.$subfolder.'_'.$sect_id.'/'.str_replace($ersetzen_mit, $ersetzt, clean_filename($material_infos->name))] = $tmp_files;
        }
    }
}

//zip files
$tempzip = tempnam($CFG->tempdir.'/', get_string('dm_materials','block_material_download').'_'.$course->shortname);
$zipper = new zip_packer();
if ($zipper->archive_to_pathname($files_zum_downloaden, $tempzip)) {
     send_temp_file($tempzip, $filename);
}

