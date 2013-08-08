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
