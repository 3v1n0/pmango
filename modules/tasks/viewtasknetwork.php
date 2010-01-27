<?php
/**
---------------------------------------------------------------------------

 PMango Project

 Title:      view TaskNetwork.

 File:       viewtasknetwork.php
 Location:   pmango/modules/tasks
 Started:    2010.11.11
 Author:     Marco Trevisan
 Type:       PHP

 This file is part of the PMango project
 Further information at: http://pmango.sourceforge.net

 Version history.
 - 2010.01.27
   0.1 first version

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

global $m, $a, $tab;

$project_id = dPgetParam($_REQUEST, 'project_id', 0);
$tab = dPgetParam($_REQUEST, 'tab', 0);

if (!empty($_POST)) {
	$task_level = $AppUI->getState('ExplodeTasks');
	$new_task_level = dPgetParam($_POST, 'explode_tasks', '1');
	
	if ($task_level != $new_task_level) {
		$task_level = $new_task_level;
		$AppUI->setState('ExplodeTasks', $task_level);
		$AppUI->setState('tasks_opened', array());
		$AppUI->setState('tasks_closed', array());
	}
}

$show_names     = dPgetBoolParam($_POST, 'show_names');
$show_progress  = dPgetBoolParam($_POST, 'show_progress');
$show_alerts    = dPgetBoolParam($_POST, 'show_alerts');
$show_p_data    = dPgetBoolParam($_POST, 'show_p_data');
$show_a_data    = dPgetBoolParam($_POST, 'show_a_data');
$show_p_res     = dPgetBoolParam($_POST, 'show_p_res');
$show_a_res     = dPgetBoolParam($_POST, 'show_a_res');
$show_p_time    = dPgetBoolParam($_POST, 'show_p_time');
$show_a_time    = dPgetBoolParam($_POST, 'show_a_time');
$show_vertical  = dPgetBoolParam($_POST, 'show_vertical');
$show_def_dep   = dPgetBoolParam($_POST, 'show_def_dep');
$show_dep       = dPgetBoolParam($_POST, 'show_dep');
$show_all_arrow = dPgetBoolParam($_POST, 'show_all_arrow');
$show_time_gaps = dPgetBoolParam($_POST, 'show_time_gaps');
$show_cr_path   = dPgetBoolParam($_POST, 'show_cr_path');

if (!isset($_POST['show_dep']))
	$show_def_dep = true;

if (!isset($_POST['show_all_arrow']))
	$show_all_arrow = true;

$graph_img_src = "?m=tasks&suppressHeaders=1&a=tasknetwork&project_id=$project_id".
                 "&names=".($show_names ? "true" : "false").
                 "&progress=".($show_progress ? "true" : "false").
                 "&alerts=".($show_alerts ? "true" : "false").
                 "&p_data=".($show_p_data ? "true" : "false").
                 "&a_data=".($show_a_data ? "true" : "false").
                 "&p_res=".($show_p_res ? "true" : "false").
                 "&a_res=".($show_a_res ? "true" : "false").
                 "&p_time=".($show_p_time ? "true" : "false").
                 "&a_time=".($show_a_time ? "true" : "false").
                 "&vertical=".($show_vertical ? "true" : "false").
                 "&def_dep=".($show_def_dep ? "true" : "false").
                 "&dep=".($show_dep ? "true" : "false").
                 "&all_arrow=".($show_all_arrow ? "true" : "false").
                 "&time_gaps=".($show_time_gaps ? "true" : "false").
                 "&cr_path=".($show_cr_path ? "true" : "false");

?>

<style type="text/css">
	#graph {
		width: 600px;
		height: 400px;
		border: 1px solid black;
		position: relative;
		background: #fff;
	}

	abbr {
		cursor: help;
		border-bottom: 1px dotted #555;
	}
</style>

<script type="text/javascript">

var projectID = <?php  echo $project_id ?>;
var expandChanged = false;
var loader = './style/default/images/loader.gif';
var graph_error = './style/default/images/graph_loading_error.png';

var iviewer;

$(function(){
	var graphWidth = (navigator.appName == 'Netscape' ? window.innerWidth : document.body.offsetWidth) * 0.95;
	$("#graph").width(graphWidth);
});

function loadPlaceHolder(img_src) {
	$(function(){
		$("#graph").iviewer({
	           src: img_src,
	           zoom: 100,
	           zoom_min: 100,
	           zoom_max: 100,
	           update_on_resize: true,
	           ui_disabled: true,
	           initCallback: function() {
	           	   if (iviewer)
	           	       iviewer.img_object.object.remove();

	               iviewer = this;
	           },
		       onStartDrag: function(object, coords) {
			       return false;
			   }
	      });
	});
}

function loadGraph(graph_src) {
	$(function () {
		var img = new Image();
		
		$(img).load(function () {
	
			var zoom = "fit";
	
			if (img.width < $("#graph").width() && img.height < $("#graph").height())
				zoom = 100;
	
			$("#graph").iviewer({
				   zoom: zoom,
		           src: img.src,
		           zoom_min: 5,
		           zoom_max: 1000,
		           update_on_resize: true,
		           ui_disabled: false,
		           initCallback: function() {
					   if (iviewer)
			        	   iviewer.img_object.object.remove();
		        	   
					   iviewer = this;
		           }
		      });
		})
		
	    .error(function () {
	    	loadPlaceHolder(graph_error);
	    })
	
	    .attr('src', graph_src);
	});
}

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

function buildGraphUrl() {
	var show_names = $("#show_names:checked").val();
	var show_alerts = $("#show_alerts:checked").val();
	var show_progress = $("#show_progress:checked").val();
	var show_p_data = $("#show_p_data:checked").val();
	var show_a_data = $("#show_a_data:checked").val();
	var show_p_res = $("#show_p_res:checked").val();
	var show_a_res = $("#show_a_res:checked").val();
	var show_p_time = $("#show_p_time:checked").val();
	var show_a_time = $("#show_a_time:checked").val();
	var show_vertical = $("#vertical:checked").val();
	var show_def_dep = $("#def_dep:checked").val();
	var show_dep = $("#dep:checked").val();
	var show_all_arrow = $("#all_arrow:checked").val();
	var show_time_gaps = $("#time_gaps:checked").val();
	var show_cr_path = $("#cr_path:checked").val();
	
	var url = "?m=tasks&suppressHeaders=1&a=tasknetwork&project_id="+projectID+
		      "&names="+(show_names ? "true" : "false")+
		      "&progress="+(show_progress ? "true" : "false")+
		      "&alerts="+(show_alerts ? "true" : "false")+
		      "&p_data="+(show_p_data ? "true" : "false")+
		      "&a_data="+(show_a_data ? "true" : "false")+
		      "&p_res="+(show_p_res ? "true" : "false")+
		      "&a_res="+(show_a_res ? "true" : "false")+
		      "&p_time="+(show_p_time ? "true" : "false")+
		      "&a_time="+(show_a_time ? "true" : "false")+
		      "&vertical="+(show_vertical ? "true" : "false")+
	          "&def_dep="+(show_def_dep ? "true" : "false")+
	          "&dep="+(show_dep ? "true" : "false")+
	          "&all_arrow="+(show_all_arrow ? "true" : "false")+
	          "&time_gaps="+(show_time_gaps ? "true" : "false")+
	          "&cr_path="+(show_cr_path ? "true" : "false");

	if (expandChanged) {
		url += '&explode_tasks='+$("#explode_tasks").val();
		expandChanged = false;
	}
	
    return url;
}

function doSubmit() {
	document.editFrm.submit(); //TODO enable on old browsers 
//	loadPlaceHolder(loader);
//	loadGraph(buildGraphUrl());
}

loadPlaceHolder(loader);
loadGraph('<?php  echo $graph_img_src; ?>');

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
						<td class="tab_setting_title">&nbsp;</td>
						<td align="left">
							<input type='checkbox' id="time_gaps" name='time_gaps' <? echo $show_time_gaps ? 'checked="checked" ' : '' ?>/>
							<label for="time_gaps"><?php echo $AppUI->_('Time Gaps'); ?></label>
						</td>
					</tr>
				</table>
			</td>
			<td align='left' valign="top" style="border-right: solid transparent 20px;">
				<table border="0" cellspacing="0">
					<tr>
						<td class="tab_setting_title">&nbsp;</td>
						<td align="left">
							<input type='checkbox' id="vertical" name='vertical' <? echo $show_vertical ? 'checked="checked" ' : '' ?>/>
							<label for="vertical"><?php echo $AppUI->_('Vertical View'); ?></label>
						</td>
					</tr>
					<tr>
						<td class="tab_setting_title">&nbsp;</td>
						<td align="left">
							<input type='checkbox' id="def_dep" name='def_dep' <? echo $show_def_dep ? 'checked="checked" ' : '' ?>/>
							<label for="def_dep"><?php echo $AppUI->_('Default Dependencies'); ?></label>
						</td>
					</tr>
					<tr>
						<td class="tab_setting_title">&nbsp;</td>
						<td align="left">
							<input type='checkbox' id="dep" name='dep' <? echo $show_dep ? 'checked="checked" ' : '' ?>/>
							<label for="dep"><?php echo $AppUI->_('Dependencies'); ?></label>
						</td>
					</tr>
					<tr>
						<td class="tab_setting_title">&nbsp;</td>
						<td align="left">
							<input type='checkbox' id="cr_path" name='cr_path' <? echo $show_cr_path ? 'checked="checked" ' : '' ?>/>
							<label for="cr_path"><?php echo $AppUI->_('Critical Path'); ?></label>
						</td>
					</tr>
				</table>
			</td>
			<td align='left' valign="top" style="border-right: solid transparent 20px;">
				<table border="0" cellspacing="0">
					<tr>
						<td class="tab_setting_title"><?php echo $AppUI->_('Planned Info'); ?>:</td>
						<td align="left">
							<input type='checkbox' id="show_p_data" name='show_p_data' <? echo $show_p_data ? 'checked="checked" ' : '' ?>/>
							<label for="show_p_data">
								<abbr title="<?php echo $AppUI->_('Duration, effort and cost'); ?>">
									<?php echo $AppUI->_('Data'); ?>
								</abbr>
							</label>
						</td>
					</tr>
					<tr>
						<td class="tab_setting_title">&nbsp;</td>
						<td align="left">
							<input type='checkbox' id="show_p_res" name='show_p_res' onclick="resourceSelectSwap(false);" <? echo $show_p_res ? 'checked="checked" ' : '' ?>/>
							<label for="show_p_res">
								<abbr title="<?php echo $AppUI->_('Personal Effort, Person and Role'); ?>">
									<?php echo $AppUI->_('Resources'); ?>
								</abbr>
							</label>
						</td>
					</tr>
					<tr>
						<td class="tab_setting_title">&nbsp;</td>
						<td align="left">
							<input type='checkbox' id="show_p_time" name='show_p_time' <? echo $show_p_time ? 'checked="checked" ' : '' ?>/>
							<label for="show_p_time">
								<abbr title="<?php echo $AppUI->_('Start and End dates'); ?>">
									<?php echo $AppUI->_('Timeframe'); ?>
								</abbr>
							</label>
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
							<label for="show_a_data">
								<abbr title="<?php echo $AppUI->_('Duration, effort and cost'); ?>">
									<?php echo $AppUI->_('Data'); ?>
								</abbr>
							</label>
						</td>
					</tr>
					<tr>
						<td class="tab_setting_title">&nbsp;</td>
						<td align="left">
							<input type='checkbox' id="show_a_res" name='show_a_res' onclick="resourceSelectSwap(true);" <? echo $show_a_res ? 'checked="checked" ' : '' ?>/>
							<label for="show_a_res">
								<abbr title="<?php echo $AppUI->_('Personal Effort, Person and Role'); ?>">
									<?php echo $AppUI->_('Resources'); ?>
								</abbr>
							</label>
						</td>
					</tr>
					<tr>
						<td class="tab_setting_title">&nbsp;</td>
						<td align="left">
							<input type='checkbox' id="show_a_time" name='show_a_time' <? echo $show_a_time ? 'checked="checked" ' : '' ?>/>
							<label for="show_a_time">
								<abbr title="<?php echo $AppUI->_('Start and End dates'); ?>">
									<?php echo $AppUI->_('Timeframe'); ?>
								</abbr>
							</label>
						</td>
					</tr>
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
			<td align='left' valign="top" style="border-right: solid transparent 20px;">
				<table border="0" cellspacing="0">
					
<!--					
					<tr>
						<td class="tab_setting_title">&nbsp;</td>
						<td align="left">
							&nbsp; <input type="button" class="button" value="<?php echo $AppUI->_( 'Update' );?>"  onclick='doSubmit();'>
						</td>
					</tr>
					<tr>
						<td class="tab_setting_title">&nbsp;</td>
						<td align="left">
							&nbsp; <input type="button" class="button" value="<?php echo $AppUI->_( 'Done' );?>"  onclick='displayItemSwitch("tab_content", "tab_settings_content");'>
						</td>
					</tr>
-->
				</table>
			</td>
				
			<td align='left' valign="bottom" style="border-right: solid transparent 20px;">
				<table border="0" cellspacing="0">
					<tr>
						<td align="left">
							&nbsp; <input type="button" class="button" value="<?php echo $AppUI->_( 'Draw' );?>"  onclick='doSubmit();'>
						</td>
					</tr>
					<tr>
						<td align="left">
							&nbsp; <input type="button" class="button" value="<?php echo $AppUI->_( 'Done' );?>"  onclick='displayItemSwitch("tab_content", "tab_settings_content");'>
						</td>
					</tr>
				</table>
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
