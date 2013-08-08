<?php

class block_material_download extends block_base {

    public function init() {
        $this->title = get_string('material_download', 'block_material_download');
    }
    
    public function get_content() {
        global $DB, $CFG, $OUTPUT, $COURSE;
        require_once("$CFG->libdir/resourcelib.php");

        if ($this->content !== null) {
          return $this->content;
        }

    $this->content         =  new stdClass;
    $resources['resource'] = 'Datei(en)';
    $resources['folder'] = 'Verzeichnis(se)';
    
    $modinfo = get_fast_modinfo($COURSE);
    
    $meldung = '';
    
    foreach ($modinfo->instances as $modname=>$instances) {
        if(array_key_exists($modname, $resources)) {
            $ii = 0;
            foreach($instances as $instances_id=>$instance) {
                if (!$instance->uservisible) {
                    continue;
                }
                $ii++;
            }
            
            if($ii > 0) $meldung .= $ii.' '.$resources[$modname].'<br />';
        }
    }
    
    if($meldung != '') {
        $this->content->text = $meldung;
        $this->content->footer = '<a href="'.$CFG->wwwroot.'/blocks/material_download/download_materialien.php?courseid='.($COURSE->id).'">Download .zip-Datei</a>';
    } else {
        $this->content->text = '(Keine Dateien vorhanden)';
    }
    
    return $this->content;
  }
  
  
}
?>
