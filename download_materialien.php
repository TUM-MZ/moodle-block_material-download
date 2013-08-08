<?php

// Verpackt die Dateien und Verzeichnisse eines Kurses in einer .zip-Datei und sendet sie dem Browser zum Downloaden

require_once ('../../config.php');
require_once ($CFG->dirroot . '/lib/filelib.php');
require_once ($CFG->dirroot . '/lib/moodlelib.php');

$courseid = required_param('courseid', PARAM_INT); 
$course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
$context = context_course::instance($courseid);

$PAGE->set_url('/mod/course/view.php', array('id' => $courseid));
$user = $USER;

$fs = get_file_storage();
$zipper = get_file_packer('application/zip');
$filename = str_replace(' ', '_', clean_filename($course->id."-".$course->shortname."-".date("Ymd").".zip")); //name of new zip file.

$ersetzen_mit = array('Ä', 'ä', 'Ö', 'ö', 'Ü', 'ü', 'ß', ' ');
$ersetzt = array('Ae', 'ae', 'Oe', 'oe', 'Ue', 'ue', 'ss', '_');

$resources['resource'] = 'Datei(en)';
$resources['folder'] = 'Verzeichnis(se)';
    
$modinfo = get_fast_modinfo($course);
$cms = array();
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


foreach ($materialien as $material_name => $einzelnen_materialien) {
    $anzahl = count($einzelnen_materialien);
    for ($ii=0; $ii<$anzahl; $ii++) {
        $material_infos = $cms[$einzelnen_materialien[$ii]];
        
        if($material_name == 'resource') {
            $tmp_files=$fs->get_area_files($material_infos->context->id, 'mod_'.$material_name, 'content', false, 'sortorder DESC', false);
            
         if(count($tmp_files) > 1 && $material_name == 'resource' && !has_capability('moodle/course:viewhiddenactivities', $context)) {
            
//  Nur die Hauptdatei zippen 
            reset($tmp_files);
            $tmp_file = current($tmp_files);
            $files_zum_downloaden['Datei_'.$ii.'_'.str_replace($ersetzen_mit, $ersetzt, clean_filename($tmp_file->get_filename()))] = $tmp_file;
        } else {
            
//  Dozenten dürfen alle Dateien herunterladen            
            foreach($tmp_files as $tmp_file) {
                $files_zum_downloaden['Datei_'.$ii.'_'.str_replace($ersetzen_mit, $ersetzt, clean_filename($tmp_file->get_filename()))] = $tmp_file;
            }
        }
           
            
        } else {
            $tmp_files=$fs->get_file($material_infos->context->id, 'mod_'.$material_name, 'content', '0', '/', '.');
            $files_zum_downloaden['Verzeichnis_'.$ii.'_'.str_replace($ersetzen_mit, $ersetzt, clean_filename($material_infos->name))] = $tmp_files;

        }
        
    }
}


//zip files
$tempzip = tempnam($CFG->tempdir.'/', 'materialien_'.$course->shortname);
$zipper = new zip_packer();
if ($zipper->archive_to_pathname($files_zum_downloaden, $tempzip)) {
     send_temp_file($tempzip, $filename);
}




?>
