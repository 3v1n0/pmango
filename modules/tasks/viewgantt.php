<?php
/**
---------------------------------------------------------------------------

 PMango Project

 Title:      view Gantt.

 File:       viewgantt.php
 Location:   pmango\modules\tasks
 Started:    2005.09.30
 Author:     Lorenzo Ballini
 Type:       PHP

 This file is part of the PMango project
 Further information at: http://pmango.sourceforge.net

 Version history.
 - 2006.07.30 Lorenzo
   Second version, modified to view PMango Gantt.
 - 2006.07.30 Lorenzo
   First version, unmodified from dotProject 2.0.1.

---------------------------------------------------------------------------

 PMango - A web application for project planning and control.

 Copyright (C) 2006 Giovanni A. Cignoni, Lorenzo Ballini, Marco Bonacchi
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

GLOBAL $min_view, $m, $a;

$min_view = defVal( @$min_view, false);

$project_id = defVal( @$_GET['project_id'], 0);

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

$sql = "SELECT project_start_date, project_finish_date FROM projects WHERE project_id = $project_id";
$tmp = db_loadList($sql);
$project_dates = $tmp[0];

// sdate and edate passed as unix time stamps
$sdate = dPgetParam( $_POST, 'sdate', 0 );
$edate = dPgetParam( $_POST, 'edate', 0 );
$showLabels = dPgetParam( $_POST, 'showLabels', '0' );
//if set GantChart includes user labels as captions of every GantBar
if ($showLabels!='0') {
    $showLabels='1';
}
$showWork = dPgetParam( $_POST, 'showWork', '0' );
if ($showWork!='0') {
    $showWork='1';
}

$show_names = dPgetParam($_POST, 'show_names', false);
if (empty($_POST)) $show_names = true;

$show_deps = dPgetParam($_POST, 'show_dependencies', false);
$show_bw = dPgetParam($_POST, 'show_bw', false);

// months to scroll
$scroll_date = 1;

$display_option = dPgetParam( $_POST, 'display_option', 'all' );

// format dates
$df = $AppUI->getPref('SHDATEFORMAT');

$project_start = new CDate($project_dates['project_start_date']);
$project_end = new CDate($project_dates['project_finish_date']);

switch($display_option) {
	case 'custom':
		$start_date = intval($sdate) ? new CDate($sdate) : new CDate();
		$end_date = intval($edate) ? new CDate($edate) : new CDate();
		break;
	case 'from_start':
		$start_date = $project_start;
		$end_date = new CDate();
		$end_date->addDays(1);
		break;
	case 'to_end':
		$start_date = new CDate();
		
		if ($start_date->after($project_end))
			$start_date = $project_start;
		
		$start_date->addDays(-1);
		$end_date = $project_end;
		break;
	case 'month':
		$start_date = new CDate();
		$start_date->addMonths( -$scroll_date );
		$end_date = new CDate();
		$end_date->addMonths( $scroll_date );
	default:
		$start_date = $project_start;
		$end_date = $project_end;
		break;
}

// setup the title block
if (!@$min_view) {
	$titleBlock = new CTitleBlock( 'Gantt Chart', 'applet-48.png',$m, "$m.$a" );
	//$titleBlock->addCrumb( "?m=tasks", "tasks list" );
	$titleBlock->addCrumb( "?m=projects&a=view&project_id=$project_id", "View project" );
	$titleBlock->show();
}

$graph_img_src = "?m=tasks&a=gantt&suppressHeaders=1&project_id=$project_id" .
	  "&show_names=".($show_names ? "true" : "false")."&draw_deps=".($show_deps ? "true" : "false").
	  "&colors=".($show_bw ? "false" : "true").
	  ($display_option == 'all' ? '' :
		'&start_date='.$start_date->format("%Y-%m-%d").'&finish_date='.$end_date->format( "%Y-%m-%d" ));
?>
<script language="javascript">
var projectID = <?php  echo $project_id ?>;
var graphWidth = (navigator.appName == 'Netscape' ? window.innerWidth : document.body.offsetWidth) * 0.95;
var expandChanged = false;
var calendarField = '';

function popCalendar( field ){
	calendarField = field;
	idate = eval( 'document.editFrm.' + field + '.value' );
	window.open( 'index.php?m=public&a=calendar&dialog=1&callback=setCalendar&date=' + idate, 'calwin', 'width=250, height=220, scollbars=false' );
}

/**
 *	@param string Input date in the format YYYYMMDD
 *	@param string Formatted date
 */
function setCalendar( idate, fdate ) {
	fld_date = eval( 'document.editFrm.' + calendarField );
	fld_fdate = eval( 'document.editFrm.show_' + calendarField );
	fld_date.value = idate;
	fld_fdate.value = fdate;

	var radios = document.editFrm.time_interval;

	for(var i = 0; i < radios.length; i++) {
		radios[i].checked = false;
		if(radios[i].id == "custom") {
			radios[i].checked = true;
		}
	}

	document.editFrm.display_option.value = 'custom';
}

function scrollPrev() {
	f = document.editFrm;
<?php
	$new_start = $start_date;
	$new_end = $end_date;
	$new_start->addMonths( -$scroll_date );
	$new_end->addMonths( -$scroll_date );
	echo "f.sdate.value='".$new_start->format( FMT_TIMESTAMP_DATE )."';";
	echo "f.edate.value='".$new_end->format( FMT_TIMESTAMP_DATE )."';";
?>
	document.editFrm.display_option.value = 'custom';
	f.submit();
}

function scrollNext() {
	f = document.editFrm;
<?php
	$new_start = $start_date;
	$new_end = $end_date;
	$new_start->addMonths( $scroll_date+1 );
	$new_end->addMonths( $scroll_date+1 );
	echo "f.sdate.value='" . $new_start->format( FMT_TIMESTAMP_DATE ) . "';";
	echo "f.edate.value='" . $new_end->format( FMT_TIMESTAMP_DATE ) . "';";
?>
	document.editFrm.display_option.value = 'custom';
	f.submit();
}

function showFromStart() {
	document.editFrm.display_option.value = "from_start";
	document.editFrm.submit();
}

function showToEnd() {
	document.editFrm.display_option.value = "to_end";
	document.editFrm.submit();
}

function showThisMonth() {
	document.editFrm.display_option.value = "this_month";
	document.editFrm.submit();
}

function showFullProject() {
	document.editFrm.display_option.value = "all";
	document.editFrm.submit();
}

$(function(){
	$("#graphloader").width(graphWidth);
	$("#graphloader").height(400);
	$("#graphloader").css("background-color", "#fff");
});

function loadGraph(src) { 
	$(function () {
		var img = new Image();
	
		$(img).load(function () {
			$(this).hide();
			$('#graphloader').hide();
			$('#graph').append(this);
			$('#graph').show();
	      	$(this).fadeIn();
		})
		
	    .error(function () {
	    	var errimg = new Image();

	    	$(errimg).load(function () {
				$(this).hide();
				$('#graphloader').hide();
				$('#graph').append(this);
				$('#graph').show();
		      	$(this).fadeIn();
			})

			.attr('src', './style/default/images/graph_loading_error.png');
	    })
		
	    .attr('src', src+'&width='+graphWidth);
	});
}

function buildGraphUrl() {
	var show_names = $("#show_names:checked").val();
	var show_deps = $("#show_deps:checked").val();
	var show_bw = $("#show_bw:checked").val();
	var start_date = document.editFrm.sdate.value;
	var end_date = document.editFrm.edate.value;
	var explode = document.editFrm.explode_tasks.value;

	var url = '?m=tasks&a=gantt&suppressHeaders=1&project_id='+projectID+
	          '&show_names='+(show_names ? 'true' : 'false')+
	          '&draw_deps='+(show_deps ? 'true' : 'false')+
			  '&colors='+(show_bw ? 'false' : 'true');

	if (expandChanged) {
		url += '&explode_tasks='+explode;
		expandChanged = false;
	}

	if (document.editFrm.display_option.value != 'all') {
		url += 'start_date='+start_date+'&finish_date='+end_date;
	}

	return url;
}

function doSubmit() {

	if (document.editFrm.edate.value < document.editFrm.sdate.value)
		alert("Start date must before end date");
	else {
		//document.editFrm.submit(); //TODO enable on old browsers 
		$('#graph').empty();
		$('#graphloader').fadeIn();
		loadGraph(buildGraphUrl());
	}
}

loadGraph('<?php echo $graph_img_src; ?>');
</script>

<form name="editFrm" method="post" action="?<?php echo "m=$m&a=$a&project_id=$project_id";?>">
<input type="hidden" name="display_option" value="<?php echo $display_option;?>" />

<table id='tab_settings_content' border="0" cellpadding="4" cellspacing="0" align="center" style="display: none">

<tr>
	<td align="left" valign="top" width="20">
<?php
	$new_start->addMonths( -$scroll_date );
	$new_end->addMonths( -$scroll_date );
?>
	</td>

	<td align='left' valign="top" style="border-right: solid transparent 20px;">
		<table border="0" cellspacing="0">
			<tr>
				<td class="tab_setting_title"><?php echo $AppUI->_('Draw');?>:</td>
				<td>
					<input type='checkbox' id='show_names' name='show_names' <? echo $show_names ? 'checked="checked"' : '' ?> />
					<label for="show_names"><?php echo $AppUI->_('Task Names'); ?></label>
				</td>
			</tr>
			<tr>
				<td class="tab_setting_title">&nbsp;</td>
				<td align="left">
					<input type='checkbox' id='show_deps' name='show_dependencies' <? echo $show_deps ? 'checked="checked"' : '' ?> />
					<label for="show_deps"><?php echo $AppUI->_('Dependencies'); ?></label>
				</td>
			</tr>
			<tr>
				<td class="tab_setting_title">&nbsp;</td>
				<td>
					<input type='checkbox' id='show_bw' name='show_bw'" <? echo $show_bw ? 'checked="checked"' : '' ?> />
					<label for="show_bw"><?php echo $AppUI->_('Printable BW colors'); ?></label>
				</td>
			</tr>
			<tr>
				<td class="tab_setting_title">
					<?php echo $AppUI->_('Size').": ";?>
				</td>
				<td>&nbsp;
					<select name="image_size" class="text" onchange=""> <!-- add custom inputs onchange -->
						<option value="1" selected="selected">Default</option>
						<option value="2" >Custom</option>
						<option value="3" >Fit in Window</option>
					</select>
				</td>
			</tr>
		</table>
	</td>

	<td align='left' valign="top" style="border-right: solid transparent 20px;">
		<table border="0" cellspacing="0">
			<tr>
				<td class="tab_setting_title"><?php echo $AppUI->_('Show');?>:</td>
				<td align="left">
					<input type='radio' id='whole_project' name='time_interval' onclick="showFullProject();" <? echo $display_option == "all" ? 'checked="checked"' : '' ?> />
					<label for="whole_project"><?php echo $AppUI->_('Whole Project'); ?></label>
				</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td>
					<input type='radio' id='from_start' name='time_interval' onclick='showFromStart();' <? echo $display_option == "from_start" ? 'checked="checked"' : '' ?> />
					<label for="from_start"><?php echo $AppUI->_('From Start'); ?></label>
				</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td>
					<input type='radio' id='to_end' name='time_interval' onclick='showToEnd();' <? echo $display_option == "to_end" ? 'checked="checked"' : '' ?> />
					<label for="to_end"><?php echo $AppUI->_('To End'); ?></label>
				</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td>
					<input type='radio' id='custom' name='time_interval' disabled="disabled" <? echo $display_option == "custom" ? 'checked="checked"' : '' ?> />
					<label for="custom"><?php echo $AppUI->_('Custom'); ?></label>
				</td>
			</tr>
		</table>
	</td>

	<td align='left' valign="top" style="border-right: solid transparent 20px">
		<table border="0" cellspacing="0">
			<tr>
				<td class="tab_setting_title">
					<?php echo $AppUI->_('From');?>:
				</td>
				<td>
					<input type="hidden" name="sdate" value="<?php echo $start_date->format( FMT_TIMESTAMP_DATE );?>" />
					<input type="text" class="text" name="show_sdate" value="<?php echo $start_date->format( $df );?>" size="12" disabled="disabled" />
					<a href="#" onclick="popCalendar('sdate');"><img src="./images/calendar.gif" width="24" height="12" alt="" border="0"></a>
				</td>
			</tr>
			<tr>
				<td class="tab_setting_title">
					<?php echo $AppUI->_( 'To' );?>:
				</td>
				<td align="left" nowrap="nowrap">
				    <input type="hidden" name="edate" value="<?php echo $end_date->format( FMT_TIMESTAMP_DATE );?>" />
					<input type="text" class="text" name="show_edate" value="<?php echo $end_date->format( $df );?>" size="12" disabled="disabled" />
					<a href="#" onclick="popCalendar('edate');"><img src="./images/calendar.gif" width="24" height="12" alt="" border="0"></a>
				<!--<td valign="top">
					<input type="checkbox" name="showLabels" <?php //echo (($showLabels==1) ? "checked=true" : "");?>><?php //echo $AppUI->_( 'Show captions' );?>
					</td>
					<td valign="top">
						<input type="checkbox" name="showWork" <?php //echo (($showWork==1) ? "checked=true" : "");?>><?php //echo $AppUI->_( 'Show work instead of duration' );?>
					</td>-->
				</td>
			</tr>
			<tr>				<td class="tab_setting_title">
					<?php echo $AppUI->_('Explode tasks').": ";?>
				</td>
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

	<input type="hidden" name="reset_level" value="1" />
	<td valign="bottom" align="left">
		<input type="button" class="button" value="<?php echo $AppUI->_( 'Draw' );?>" onclick='doSubmit();'>
	</td>
</tr>
</table>
</form>

<form id='tab_content' name='pdf_options' method='POST' action='<?php echo $query_string; ?>'>
	<table id='' width='100%' border='0' cellpadding='1' cellspacing='0'>
		<tr align="right">
			<td align="right">

			</td>
		</tr>
		<tr>
			<td align="right">
			<?if ($_POST['make_pdf']=="true")	{
				include('modules/report/makePDF.php');

				$task_level=$explodeTasks;
				$q  = new DBQuery;
				$q->addQuery('projects.project_name');
				$q->addTable('projects');
				$q->addWhere("project_id = $project_id ");
				$name = $q->loadList();

				$q  = new DBQuery;
				$q->addTable('groups');
				$q->addTable('projects');
				$q->addQuery('groups.group_name');
				$q->addWhere("projects.project_group = groups.group_id and projects.project_id = '$project_id'");
				$group = $q->loadList();

				foreach ($group as $g){
					$group_name=$g['group_name'];
				}

				$pdf = PM_headerPdf($name[0]['project_name'],'P',1,$group_name);
				PM_makeTaskPdf($pdf, $project_id, $task_level, $tasks_closed, $tasks_opened, $roles, $tview, $start_date, $end_date, $showIncomplete); //TODO show mine!
				if ($tview) $filename=PM_footerPdf($pdf, $name[0]['project_name'], 2);
				else $filename=PM_footerPdf($pdf, $name[0]['project_name'], 1);
				?>
				<a href="<?echo $filename;?>"><img src="./modules/report/images/pdf_report.gif" alt="PDF Report" border="0" align="absbottom"></a><?
			}?>


				<input type="hidden" name="make_pdf" value="false" />
				<input type="button" class="button" value="<?php echo $AppUI->_( 'Generate PDF ' );?>" onclick='document.pdf_options.make_pdf.value="true"; document.pdf_options.submit();'>
			<? if($tview==0){?>
				<input type="hidden" name="addreport" value="-1" />
				<input type="button" class="button" value="<?php echo $AppUI->_( 'Add to Report ' );?>" onclick='document.pdf_options.addreport.value="1"; document.pdf_options.submit();'><?}
			else{?>
				<input type="hidden" name="addreport" value="-1" />
				<input type="button" class="button" value="<?php echo $AppUI->_( 'Add to Report ' );?>" onclick='document.pdf_options.addreport.value="2"; document.pdf_options.submit();'><?}?>
			</td>
		</tr>
	</table>
</form>

<br />
<table cellspacing="0" cellpadding="0" border="1" align="center" style="border-top-style: hidden;">
<?php if ($display_option != "all") { ?>
	<tr style="border-style: hidden;">
		<td align="left" style="border-top-style: hidden; border-left-style: hidden; border-right-style: hidden;">
			<a href="#" onclick="scrollPrev();">
	  			<img src="./images/prev.gif" width="16" height="16" alt="<?php echo $AppUI->_( 'previous' );?>" border="0">
	 		</a>
		</td>
		<td align="right" style="border-top-style: hidden; border-left-style: hidden; border-right-style: hidden;">
			<a href="#" onclick="scrollNext();">
	  			<img src="./images/next.gif" width="16" height="16" alt="<?php echo $AppUI->_( 'next' );?>" border="0">
	 		</a>
		</td>
	</tr>
<?php } ?>
	<tr>
		<td <?php if ($display_option != "all") echo "colspan='2'" ?>>
<?php
if (db_loadResult( "SELECT COUNT(*) FROM tasks WHERE task_project=$project_id" )) {
?>
		<div id="graphloader"></div>
		<div id="graph"></div>
<?php
} else {
	echo $AppUI->_( "No tasks to display" );
}
?>
		</td>
	</tr>
</table>
<br />