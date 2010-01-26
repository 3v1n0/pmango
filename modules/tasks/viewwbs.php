<?php
/**
---------------------------------------------------------------------------

 PMango Project

 Title:      view WBS.

 File:       viewwbs.php
 Location:   pmango/modules/tasks
 Started:    2009.11.11
 Author:     Marco Trevisan
 Type:       PHP

 This file is part of the PMango project
 Further information at: http://pmango.sourceforge.net

 Version history.
 - 2010.01.26
   0.5 added controls
 - 2009.11.11
   0.1 first stub

---------------------------------------------------------------------------

 PMango - A web application for project planning and control.

 Copyright (C) 2009-2010 Marco Trevisan (Treviño) <mail@3v1n0.net>
 All rights reserved.

 PMango reuses part of the code of dotProject 2.0.1: dotProject code is
 released under GNU GPL, further information at: http://www.dotproject.net
 Copyright (C) 2003-2005 The dotProject Development Team.

 Other libraries used by PMango are redistributed under their own license.
 See ReadMe.txt in the root folder for details.

 PMango is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation Inc., 51 Franklin St, 5th Floor, Boston, MA 02110-1301 USA.

---------------------------------------------------------------------------
*/

global $m, $a;

$project_id = dPgetParam($_GET, 'project_id', 0);

$tasks_closed = $AppUI->getState("tasks_closed");
$tasks_opened = $AppUI->getState("tasks_opened");

if (!empty($_POST)) {
	$task_level = dPgetParam($_POST, 'explode_tasks', '1');
	$AppUI->setState('ExplodeTasks', $task_level);
	$AppUI->setState('tasks_opened', array());
	$AppUI->setState('tasks_closed', array());
}

$show_names    = dPgetBoolParam($_POST, 'show_names');
$show_progress = dPgetBoolParam($_POST, 'show_progress');
$show_alerts   = dPgetBoolParam($_POST, 'show_alerts');
$show_p_data   = dPgetBoolParam($_POST, 'show_p_data');
$show_a_data   = dPgetBoolParam($_POST, 'show_a_data');
$show_p_res    = dPgetBoolParam($_POST, 'show_p_res');
$show_a_res    = dPgetBoolParam($_POST, 'show_a_res');
$show_p_time   = dPgetBoolParam($_POST, 'show_p_time');
$show_a_time   = dPgetBoolParam($_POST, 'show_a_time');

$graph_img_src = "?m=tasks&suppressHeaders=1&a=wbs&project_id=$project_id" .
	             "&names=".($show_names ? "true" : "false").
	             "&progress=".($show_progress ? "true" : "false").
	             "&alerts=".($show_alerts ? "true" : "false").
      	         "&p_data=".($show_p_data ? "true" : "false").
      	         "&a_data=".($show_a_data ? "true" : "false").
	             "&p_res=".($show_p_res ? "true" : "false").
	             "&a_res=".($show_a_res ? "true" : "false").
      	         "&p_time=".($show_p_time ? "true" : "false").
      	         "&a_time=".($show_a_time ? "true" : "false");
?>

<style type="text/css">
		.graph {
             width: 600px;
             height: 400px;
             border: 1px solid black;
             position: relative;
             background: #fff;
         }
</style>

<script type="text/javascript">

var expandChanged = false;
var iviewer;

$(function(){
	var graphWidth = (navigator.appName == 'Netscape' ? window.innerWidth : document.body.offsetWidth) * 0.95;
	$("#graph").width(graphWidth);
});

$(function(){
	$("#graph").iviewer({
           src: "./style/default/images/loader.gif",
           zoom: 100,
           zoom_min: 5,
           zoom_max: 1000,
           update_on_resize: true,
           ui_disabled: true,
           initCallback: function() {
               iviewer = this;
           }
      });
});

$(function () {
	var img = new Image();
	
	$(img).load(function () {
		iviewer.img_object.object.remove();
		$("#graph").iviewer({
	           src: img.src,
	           zoom_min: 5,
	           zoom_max: 1000,
	           update_on_resize: true,
	           ui_disabled: false,
	           initCallback: function() {
	               iviewer = this;
	           }
	      });
	})
	
    .error(function () {
     iviewer.loadImage('./style/default/images/graph_loading_error.png');
    })

    .attr('src', '<?php  echo $graph_img_src; ?>');
});

$(function() {
	$("#graph").resizable({
			minHeight: 300,
			minWidth: 400,

			stop: function(event, ui) {
				iviewer.update_container_info();
				if (iviewer.settings.zoom == "fit")
					iviewer.fit();
				else
					iviewer.set_zoom(iviewer.current_zoom);
			}
	});
});

function resourceSelectSwap(actual) {
	if (actual) {
		document.editFrm.show_p_res.checked = false;
	} else {
		document.editFrm.show_a_res.checked = false;
	}
}

</script>
<form name="editFrm" method="post" action="?<?php echo "m=$m&a=$a&project_id=$project_id";?>">
	<table id='tab_settings_content' style="display: none;" border='0' cellpadding='1' cellspacing='3' align="center">
		<tr>
			<td align='left' valign="top" style="border-right: solid transparent 20px;">
				<table border="0" cellspacing="0">
					<tr>
						<td class="tab_setting_title"><?php echo $AppUI->_('Show');?>:</td>
						<td align="left">
							<input type='checkbox' id="show_names" name='show_names' <? echo $show_names ? 'checked="checked" ' : '' ?>/>
							<label for="show_names"><?php echo $AppUI->_('Task Names'); ?></label>
						</td>
					</tr>
					<tr>
						<td class="tab_setting_title">&nbsp;</td>
						<td align="left">
							<input type='checkbox' id="show_alerts" name='show_alerts' <? echo $show_alerts ? 'checked="checked" ' : '' ?>/>
							<label for="show_alerts"><?php echo $AppUI->_('Alerts'); ?></label>
						</td>
					</tr>
					<tr>
						<td class="tab_setting_title">&nbsp;</td>
						<td align="left">
							<input type='checkbox' id="show_progress" name='show_progress' <? echo $show_progress ? 'checked="checked" ' : '' ?>/>
							<label for="show_progress"><?php echo $AppUI->_('Progress'); ?></label>
						</td>
					</tr>
					<tr>
				</table>
			</td>
			<td align='left' valign="top" style="border-right: solid transparent 20px;">
				<table border="0" cellspacing="0">
					<tr>
						<td class="tab_setting_title"><?php echo $AppUI->_('Planned Info'); ?>:</td>
						<td align="left">
							<input type='checkbox' id="show_p_data" name='show_p_data' <? echo $show_p_data ? 'checked="checked" ' : '' ?>/>
							<label for="show_p_data"><?php echo $AppUI->_('Data'); ?></label>
						</td>
					</tr>
					<tr>
						<td class="tab_setting_title">&nbsp;</td>
						<td align="left">
							<input type='checkbox' id="show_p_res" name='show_p_res' onclick="resourceSelectSwap(false);" <? echo $show_p_res ? 'checked="checked" ' : '' ?>/>
							<label for="show_p_res"><?php echo $AppUI->_('Resources'); ?></label>
						</td>
					</tr>
					<tr>
						<td class="tab_setting_title">&nbsp;</td>
						<td align="left">
							<input type='checkbox' id="show_p_time" name='show_p_time' <? echo $show_p_time ? 'checked="checked" ' : '' ?>/>
							<label for="show_p_time"><?php echo $AppUI->_('Timeframe'); ?></label>
						</td>
					</tr>
				</table>
			</td>
			<td align='left' valign="top" style="border-right: solid transparent 20px;">
				<table border="0" cellspacing="0">
					<tr>
						<td class="tab_setting_title"><?php echo $AppUI->_('Actual Info'); ?>:</td>
						<td align="left">
							<input type='checkbox' id="show_a_data" name='show_a_data' <? echo $show_a_data ? 'checked="checked" ' : '' ?>/>
							<label for="show_a_data"><?php echo $AppUI->_('Data'); ?></label>
						</td>
					</tr>
					<tr>
						<td class="tab_setting_title">&nbsp;</td>
						<td align="left">
							<input type='checkbox' id="show_a_res" name='show_a_res' onclick="resourceSelectSwap(true);" <? echo $show_a_res ? 'checked="checked" ' : '' ?>/>
							<label for="show_a_res"><?php echo $AppUI->_('Resources'); ?></label>
						</td>
					</tr>
					<tr>
						<td class="tab_setting_title">&nbsp;</td>
						<td align="left">
							<input type='checkbox' id="show_a_time" name='show_a_time' <? echo $show_a_time ? 'checked="checked" ' : '' ?>/>
							<label for="show_a_time"><?php echo $AppUI->_('Timeframe'); ?></label>
						</td>
					</tr>
				</table>
			</td>
			<td align='left' valign="top" style="border-right: solid transparent 20px;">
				<table border="0" cellspacing="0">
					<tr>
						<td class="tab_setting_title"><?php echo $AppUI->_('Explode tasks'); ?>:</td>
						<td>&nbsp; <select id="explode_tasks" name="explode_tasks" class="text" onchange="expandChanged=true;">
<?php
								$maxLevel=CTask::getLevel($project_id);
								$explodeTasks = $AppUI->getState('ExplodeTasks', '1');
			
						 		for($i=1; $i <=$maxLevel;$i++){
										$arr2[$i-1] = "Level ".$i;
							    		$arr[$i-1] = $i;}
			
								for($i = 0; $i < count($arr); $i++){
							    	$selected = ($arr[$i] == $explodeTasks) ? 'selected="selected"' : '';
							     	echo "<option value=\"{$arr[$i]}\" {$selected}>{$arr2[$i]}</option>\n";
							 	}
?>
							</select>
						</td>
					</tr>
				</table>
			</td>
				
			<td valign="bottom" align="left"> <!-- FIXME this form only submit works! -->
				<input type="button" class="button" value="<?php echo $AppUI->_( 'Update' );?>"  onclick='submit();'>
			</td>
		</tr>
	</table>
</form>

<br />
<table cellspacing="0" cellpadding="0" border="1" align="center" style="border-top-style: hidden;">
	<tr>
		<td>
<?php
if (db_loadResult( "SELECT COUNT(*) FROM tasks WHERE task_project=$project_id" )) {
?>
		<div id="graph" class="graph"></div>
<?php
} else {
	echo $AppUI->_( "No tasks to display" );
}
?>
		</td>
	</tr>
</table>
<br />



