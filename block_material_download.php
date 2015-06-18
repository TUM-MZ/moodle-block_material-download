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

    $this->content         = new stdClass;
    $resources['resource'] = get_string('dm_resource', 'block_material_download');
    $resources['folder']   = get_string('dm_folder',   'block_material_download');

    $modinfo = get_fast_modinfo($COURSE);

    $meldung = <<<EOF
<script type="text/javascript">
function MM_jumpMenu(targ,selObj,restore){
  eval(targ+".location='"+selObj.options[selObj.selectedIndex].value+"'");
  if (restore) selObj.selectedIndex=0;
}
</script>
EOF;
?>
<?php

    foreach ($modinfo->instances as $modname=>$instances) {
        if(array_key_exists($modname, $resources)) {
            $ii = 0;
            foreach($instances as $instances_id=>$instance) {
                if (!$instance->uservisible) {
                    continue;
                }
				$cms[$instance->id] = $instance;
            	$materialien[$instance->modname][] = $instance->id;

                $ii++;
            }

            if($ii > 0) $meldung .= $ii.' '.$resources[$modname].'<br />';
        }
    }

	$download_link = array();

	$sql_chk   = "SELECT `mdl_course_modules`.`id` FROM `mdl_course_modules` WHERE `mdl_course_modules`.`course` = '".$COURSE->id."' AND ( `mdl_course_modules`.`module` = '14' OR `mdl_course_modules`.`module` = '6' )";
	$modules = $DB->get_records_sql($sql_chk);
    foreach ($modules as $module) {
	    $checkid   = $module->id;
		$sql_sec   = "SELECT * FROM `mdl_course_sections` WHERE `mdl_course_sections`.`course` = ? AND ( `mdl_course_sections`.`sequence` LIKE ? OR `mdl_course_sections`.`sequence` LIKE ? OR `mdl_course_sections`.`sequence` LIKE ? OR `mdl_course_sections`.`sequence` = ? ) LIMIT 1";
		$row_sec   = $DB->get_records_sql($sql_sec, array($COURSE->id, $checkid.",%", '%,'.$checkid.',%', '%,'.$checkid, $checkid));
        foreach ($row_sec as $row) {
    		if(!empty($row->section)) {
    			$sect_id   = $row->section;
    			$download_link[$sect_id] = $row->name;
        }
		}
	}

    ksort($download_link);
	//$download_link = array_unique($download_link, SORT_REGULAR);
    $showlink = "";
    foreach ($download_link as $value => $text) {
    	if($COURSE->format == "topics") {
            if($text)
                $showlink .= '<option title ="'.$text.'" value="'.$CFG->wwwroot.'/blocks/material_download/download_materialien.php?courseid='.($COURSE->id).'&ccsectid='.$value.'">'.get_string('dm_resource2', 'block_material_download').' '.get_string('dm_from', 'block_material_download').' '.get_string('dm_topic', 'block_material_download').' '.$value.'</option>';
            else
                $showlink .= '<option value="'.$CFG->wwwroot.'/blocks/material_download/download_materialien.php?courseid='.($COURSE->id).'&ccsectid='.$value.'">'.get_string('dm_resource2', 'block_material_download').' '.get_string('dm_from', 'block_material_download').' '.get_string('dm_topic', 'block_material_download').' '.$value.'</option>';
        }
		if($COURSE->format == "weeks")  {
            if($text)
                $showlink .= '<option title ="'.$text.'" value="'.$CFG->wwwroot.'/blocks/material_download/download_materialien.php?courseid='.($COURSE->id).'&ccsectid='.$value.'">'.get_string('dm_resource2', 'block_material_download').' '.get_string('dm_from', 'block_material_download').' '.get_string('dm_week',  'block_material_download').' '.$value.'</option>';
            else
                $showlink .= '<option value="'.$CFG->wwwroot.'/blocks/material_download/download_materialien.php?courseid='.($COURSE->id).'&ccsectid='.$value.'">'.get_string('dm_resource2', 'block_material_download').' '.get_string('dm_from', 'block_material_download').' '.get_string('dm_week',  'block_material_download').' '.$value.'</option>';
        }
	}

    if($meldung != '') {
        $this->content->text   = $meldung .'<br />';
        $this->content->footer = '<form><select name="jumpMenu" id="jumpMenu" onchange="MM_jumpMenu(\'parent\',this,0)"><option value="'.$CFG->wwwroot.$_SERVER['PHP_SELF'].'?id='.($COURSE->id).'">'.get_string('dm_choose', 'block_material_download').'</option><option value="'.$CFG->wwwroot.'/blocks/material_download/download_materialien.php?courseid='.($COURSE->id).'&ccsectid=0">'.get_string('dm_download_files', 'block_material_download').'</option>'.$showlink.'</select></form>';
    } else {
        $this->content->text   = get_string('dm_no_file_exist', 'block_material_download');
    }

    return $this->content;
  }
}
