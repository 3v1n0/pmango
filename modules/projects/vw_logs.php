<?php 
/**
---------------------------------------------------------------------------

 PMango Project

 Title:      view logs

 File:       vw_logs.php
 Location:   PMango/modules/projects
 Started:    2005.09.30
 Author:     Lorenzo Ballini
 Type:       PHP

 This file is part of the PMango project
 Further information at: http://penelope.di.unipi.it

 Version history.
 - 2010.02.10 Marco Trevisan
   Fifth version, using an AJAX submit form.
 - 2010.02.01 Marco Trevisan
   Fourth version, configuration panel redesigned.
 - 2007.05.08 Riccardo
   Third version, modified to create PDF reports.
 - 2006.07.30 Lorenzo
   Second version, modified to manage Mango logs.
 - 2006.07.30 Lorenzo
   First version, unmodified from dotProject 2.0.1.

-------------------------------------------------------------------------------------------

 PMango - A web application for project planning and control.

 Copyright (C) 2006 Giovanni A. Cignoni, Lorenzo Ballini, Marco Bonacchi, Riccardo Nicolini
 Copyright (C) 2010 Marco Trevisan (Treviño) <mail@3v1n0.net>
 
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
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation Inc., 51 Franklin St, 5th Floor, Boston, MA 02110-1301 USA.

-------------------------------------------------------------------------------------------
*/

global $AppUI, $project_id, $canEdit, $m, $tab;

$q  = new DBQuery;
$q->addTable('users','u');
$q->addQuery("DISTINCT(u.user_id), concat(user_first_name,' ',user_last_name)");
$q->addJoin('user_projects','up',"up.project_id = $project_id");
$q->addWhere("up.user_id = u.user_id && up.proles_id > 0");
$q->addOrder('user_first_name, user_last_name');
$users = arrayMerge( array( '-1' => $AppUI->_('All Users') ), $q->loadHashList() );
$users = arrayMerge( array( '-2' => $AppUI->_('Group by User') ), $users );

$sql="SELECT projects.project_start_date FROM projects WHERE project_id ='$project_id'";
$db_start_date = db_loadColumn($sql);
$sql="SELECT projects.project_finish_date FROM projects WHERE project_id ='$project_id'";
$db_finish_date = db_loadColumn($sql);
$tab = dPgetParam($_REQUEST, 'tab', $tab);

if (isset($_POST['sdate']))	;
if (isset($_POST['edate']))	;

if (!empty($_POST) && !dPgetBoolParam($_POST, 'make_pdf') && !dPgetBoolParam($_POST, 'addreport')) {
	$pre = getProjectState('TaskLogs');
	$pre_date = getProjectState('TasksDate');
	
	setProjectSubState('TaskDates', 'Start', dPgetParam($_POST, 'sdate', $db_start_date[0]));
	setProjectSubState('TaskDates', 'End', dPgetParam($_POST, 'edate', $db_finish_date[0]));
	setProjectSubState('TaskLogs', 'hide_inactive', dPgetBoolParam($_POST, 'hide_inactive'));
	setProjectSubState('TaskLogs', 'hide_complete', dPgetBoolParam($_POST, 'hide_complete'));
	setProjectSubState('TaskLogs', 'user_filter', dPgetParam($_POST, 'user_id', -1));
	
	if (getProjectState('TaskLogs') !== $pre || getProjectState('TasksDate') !== $pre_date)
		deletePDF($project_id, PMPDF_LOG);
}

$StartDate = getProjectSubState('TaskDates', 'Start', $db_start_date[0]);
$EndDate = getProjectSubState('TaskDates', 'End', $db_finish_date[0]);
$hide_inactive = getProjectSubState('TaskLogs', 'hide_inactive', false);
$hide_complete = getProjectSubState('TaskLogs', 'hide_complete', false);
$user_id = getProjectSubState('TaskLogs', 'user_filter', -1);

$q->clear();
$q->addQuery('project_group,project_current');
$q->addTable('projects');
$q->addWhere('project_id = '.$project_id);
$ar=$q->loadList();
$prg = $ar[0]['project_group'];
$curPr = $ar[0]['project_current'] == '0';

$perms =& $AppUI->acl();
if (!$perms->checkModule('task_log', 'view','',intval($prg),1))
	$AppUI->redirect( "m=public&a=access_denied" );

$canDelete = $perms->checkModule('tasks', 'delete', '', intval($prg),1)  && $curPr;

$df1 = $AppUI->getPref('SHDATEFORMAT'); 
$df .= $df1. " " . $AppUI->getPref('TIMEFORMAT');
?>
<script language="JavaScript">
<?php
// security improvement:
// some javascript functions may not appear on client side in case of user not having write permissions
// else users would be able to arbitrarily run 'bad' functions
if ($canDelete) {
?>
function delIt2(id) {
	if (confirm( "<?php echo $AppUI->_('doDelete', UI_OUTPUT_JS).' '.$AppUI->_('Task Log', UI_OUTPUT_JS).'?';?>" )) {
		document.frmDelete2.task_log_id.value = id;
		document.frmDelete2.submit();
	}
}
<?php } ?>
</script>

<?php

$today=date("d/m/Y");

$whole_start = intval( $db_start_date[0] ) ? new CDate( $db_start_date[0] ) : new CDate();
$whole_finish = intval( $db_finish_date[0] ) ? new CDate( $db_finish_date[0] ) : new CDate();

 	$tmp_sdate = $StartDate;
	$tmp_sdate=explode('/',$tmp_sdate);
	$sdate=$tmp_sdate[2].$tmp_sdate[1].$tmp_sdate[0];

 	$tmp_edate = $EndDate;
	$tmp_edate=explode('/',$tmp_edate);
	$edate=$tmp_edate[2].$tmp_edate[1].$tmp_edate[0];

$display_option = "custom";

// format dates
$df = $AppUI->getPref('SHDATEFORMAT');

if ($display_option == 'custom') {
	// custom dates
	$start_date = intval( $sdate ) ? new CDate( $sdate ) : new CDate();
	$end_date = intval( $edate ) ? new CDate( $edate ) : new CDate();
} else {
	// month
	$start_date = new CDate();
	$end_date = new CDate();
	$end_date->addMonths( $scroll_date );
}

if (dPgetBoolParam($_POST, 'addreport')) {
	$sd = $start_date->format(FMT_DATETIME_MYSQL);
 	$ed = $end_date->format(FMT_DATETIME_MYSQL);

	CReport::addLogReport($project_id, $hide_complete, $hide_inactive, $user_id, $sd, $ed);
	
	$db_roles = db_loadList($sql);
	unsetProjectSubState('PDFReports', PMPDF_REPORT);
}

?>

<script language="javascript">
var calendarField = '';

function popCalendar( field ){
	calendarField = field;
	idate = eval( 'document.logs_list_options.' + field + '.value' );
	window.open( 'index.php?m=public&a=calendar&dialog=1&callback=setCalendar&date=' + idate, 'calwin', 'width=250, height=220, scollbars=false' );
}

/**
 *	@param string Input date in the format YYYYMMDD
 *	@param string Formatted date
 */
function setCalendar( idate, fdate ) {
	fld_date = eval( 'document.logs_list_options.' + calendarField );
	fld_fdate = eval( 'document.logs_list_options.show_' + calendarField );
	fld_date.value = idate;
	fld_fdate.value = fdate;
}

function showFullProject() {
	document.logs_list_options.show_sdate.value = "<?php echo $whole_start->format($df);?>";
	document.logs_list_options.show_edate.value = "<?php echo $whole_finish->format($df);?>";
	document.logs_list_options.sdate.value = "<?php echo $whole_start->format($df);?>";
	document.logs_list_options.edate.value = "<?php echo $whole_finish->format($df);?>";
}

function makeLogsPDF() {
	document.pdfFilter.make_pdf.value = "true";
	document.pdfFilter.addreport.value = "false";

	generatePDF('pdfFilter', 'logs_pdf_span');
	document.pdfFilter.make_pdf.value = "false";
}

function addLogsReport() {
	document.pdfFilter.addreport.value = "true";
	document.pdfFilter.make_pdf.value = "false";
	addReport('pdfFilter', 'logs_report_btn');
	document.pdfFilter.addreport.value = "false";
}

function updateLogsList() {

	if (compareDate(document.logs_list_options.show_sdate,
			       document.logs_list_options.show_edate)) {
	       document.logs_list_options.display_option.value = "custom";
	} else {
		return;
	}

	updateTabContent("logs_list_options", "project_logs", "logs_pdf_span", "logs_report_btn");
}
</script>

<form id="logs_list_options" name="logs_list_options" method='post' action="?m=projects&a=view&project_id=<?=$project_id?>&tab=<?=$tab?>">
	<input type='hidden' name='show_log_options' value='1'>
	<input type="hidden" name="display_option" value="<?php echo $display_option;?>" />
	<input type="hidden" name="roles" value="N" />
	
	<div id="tab_settings_content" style="display: none;">
		<table border='0' cellpadding='1' cellspacing='3' align="center">
			<tr>
				<td class="tab_setting_group">
					<table border="0" cellspacing="0">
						<tr>
							<td class="tab_setting_title" rowspan="2"><?php echo $AppUI->_('Hide');?>:</td>
							<td class="tab_setting_item">
								<input type='checkbox' id="hide_inactive" name='hide_inactive' <?php echo $hide_inactive ? 'checked="checked"' : '';?> />
								<label for="hide_inactive"><?php echo $AppUI->_('Inactive tasks'); ?></label>
							</td>
						</tr>
						<tr>
							<td class="tab_setting_item">
								<input type='checkbox' id='hide_complete' name='hide_complete' <?php echo $hide_complete ? 'checked="checked"' : '';?> />
								<label for="hide_complete"><?php echo $AppUI->_('Completed tasks'); ?></label>
							</td>
						</tr>
						<tr>
							<td class="tab_setting_title"><?php echo $AppUI->_('User Filter').": ";?></td>
							<td>
								<?php echo arraySelect( $users, 'user_id', 'size="1" class="text" id="medium"', $user_id )?>
							</td>
						</tr>
					</table>
				</td>
			
				<td class="tab_setting_group">
					<table border="0" cellspacing="0">
						<tr>
							<td class="tab_setting_title">
								<?php echo $AppUI->_('From');?>:
							</td>
							<td class="tab_setting_item">
								<input type="hidden" name="sdate" value="<?php echo $start_date->format( FMT_TIMESTAMP_DATE );?>" />
								<input type="text" disabled="disabled" class="text" name="show_sdate" value="<?php echo $start_date->format( $df );?>" size="12" onchange='document.logs_list_options.sdate.value=this.value; validateDate(this);' />
								<a href="#" onclick="popCalendar('sdate')"><img src="./images/calendar.gif" width="24" height="12" alt="" border="0"></a>
							</td>
						</tr>
						<tr>
							<td class="tab_setting_title">
								<?php echo $AppUI->_( 'To' );?>:
							</td>
							<td class="tab_setting_item">
							    <input type="hidden" name="edate" value="<?php echo $end_date->format( FMT_TIMESTAMP_DATE );?>" />
							    <input type="text" disabled="disabled" class="text" name="show_edate" value="<?php echo $end_date->format( $df );?>" size="12" onchange='document.logs_list_options.edate.value=this.value; validateDate(this);' />
							    <a href="#" onclick="popCalendar('edate')"><img src="./images/calendar.gif" width="24" height="12" alt="" border="0"></a>
							</td>
						</tr>
						<tr>
							<td class="tab_setting_title">
								<?php echo $AppUI->_( 'Reset' );?>:
							</td>
							<td>
								<a href="#" onclick="showFullProject()"><img src="./images/calendar2.gif" alt="Show Whole Project" title="Show Whole Project" border="0"></a>
							</td>
						</tr>
					</table>
				</td>
				
				<td align="left" valign="bottom">
					<table border="0" cellspacing="0">
						<tr>
							<td class="tab_setting_item">
								&nbsp; <input type="button" class="button" value="<?php echo $AppUI->_( 'Update' );?>"  onclick='updateLogsList();'>
							</td>
						</tr>
						<tr>
							<td class="tab_setting_item">
								&nbsp; <input type="button" class="button" value="<?php echo $AppUI->_( 'Done' );?>"  onclick='settingsTabToggle();'>
							</td>
						</tr>
					</table>
				</td>
			</tr>            
		</table>
	</div>
</form>

<form id='pdfFilter' name='pdfFilter' method='post' action='?m=projects&a=view&project_id=<?=$project_id?>&tab=<?=$tab?>'>
	<input type="hidden" name="m" value="projects"/>

	<div id='tab_content'>
		<table width='100%' border='0' cellpadding='1' cellspacing='0' style="border-top: solid transparent 2px;">
			<tr>
				<td align="right">
<? 
					if (dPgetBoolParam($_POST, 'make_pdf')) {
						generateLogPDF($project_id, $user_id, $hide_inactive, $hide_complete, $start_date, $end_date);
					}
	
					$pdf = getProjectSubState('PDFReports', PMPDF_LOG);
				
?>	
					<span id="logs_pdf_span" style="vertical-align: middle">	
<?							
					if ($pdf && file_exists($pdf)) {
?>
						<a id="logs_pdf_link" href="<?echo $pdf;?>">
							<img id="logs_pdf_icon" src="./modules/report/images/pdf_report.gif" alt="PDF Report" border="0">
						</a>
<?
					}
?>
					</span>		
				    <input type="hidden" name="make_pdf" value="false" />
					<input type="button" class="button" value="<?php echo $AppUI->_( 'Generate PDF ' );?>" onclick='makeLogsPDF();'>
					<input type="hidden" name="addreport" value="false" />
					<input type="button" class="button" value="<?php echo $AppUI->_( 'Add to Report ' );?>" onclick='addLogsReport();' id="logs_report_btn">
					<input type="button" class="button" value="<?php echo $AppUI->_( 'Configure' );?>" onclick='settingsTabToggle();'>
				</td>
			</tr>
		</table>
	</div>
</form>

<div id="project_logs">
<table border="0" cellpadding="2" cellspacing="1" width="100%" class="tbl">
<form name="frmDelete2" action="./index.php?m=tasks" method="post">
	<input type="hidden" name="dosql" value="do_updatetask">
	<input type="hidden" name="del" value="1" />
	<input type="hidden" name="task_log_id" value="0" />
</form>
<tr><!--<th></th>-->
	<th><?php echo $AppUI->_('Pr.');?></th>
	<th><?php echo $AppUI->_('Dates');?></th>
	<th><?php echo $AppUI->_('WBS');?></th>
	<th nowrap><?php echo $AppUI->_('Task Log Name');?></th>
	<th><?php echo $AppUI->_('Worker');?></th>
	<th><?php echo $AppUI->_('Role');?></th>
	<th><?php echo $AppUI->_('Effort');?></th>
	<th><?php echo $AppUI->_('Cost');?></th>
	<th width="100%"><?php echo $AppUI->_('Comments');?></th>
	<th></th>
</tr>
<?php
// Winnow out the tasks we are not allowed to view.
$project =& new CProject;

// Pull the task comments
$q  = new DBQuery;
$q->addQuery('task_log.*, t.task_id, CONCAT_WS(" ",user_first_name,user_last_name) as user_username, pr.proles_name, pr.proles_hour_cost');
$q->addTable('task_log');
$q->addJoin('users', 'u', 'u.user_id = task_log_creator');
$q->addJoin('project_roles', 'pr', 'pr.proles_id = task_log_proles_id');
$q->addJoin('tasks', 't', 'task_log_task = t.task_id');
//already included bY the setAllowedSQL function
//$q->addJoin('projects', 'p', 'task_project = p.project_id');
$q->addWhere("task_project = $project_id ");
if ($user_id>0) 
	$q->addWhere("task_log_creator=$user_id");
if ($hide_inactive) 
	$q->addWhere("task_status>=0");
if ($hide_complete) 
	$q->addWhere("task_log_progress < 100");
if ($user_id>-2)	
$q->addOrder('task_log_creation_date');
else $q->addOrder('task_log_creator');
$logs = $q->loadList();

$s = '';
$hrs = 0;
$crs = 0;
$i=0;
foreach ($logs as $row) {
 	
	$task_log_date = intval( $row['task_log_creation_date'] ) ? new CDate( $row['task_log_creation_date'] ) : null;
	$task_log_edit_date = intval( $row['task_log_edit_date'] ) ? new CDate( $row['task_log_edit_date'] ) : null;
	$task_log_start_date = intval( $row['task_log_start_date'] ) ? new CDate( $row['task_log_start_date'] ) : null;
	$task_log_finish_date = intval( $row['task_log_finish_date'] ) ? new CDate( $row['task_log_finish_date'] ) : null;
    $style = $row['task_log_problem'] ? 'background-color:#cc6666;color:#ffffff' :'';
    $style2 = ($task_log_date != $task_log_edit_date) ? $style2='background-color:#ffff00;' : '';
    
    $start=$start_date->format( FMT_TIMESTAMP_DATE );
    $end=$end_date->format( FMT_TIMESTAMP_DATE );
    $start_log=$task_log_start_date->format( FMT_TIMESTAMP_DATE );
    $end_log=$task_log_finish_date->format( FMT_TIMESTAMP_DATE );
    
    if(($start > $end_log)||($end < $start_log)){ /*do nothing*/}
	else{
    	$s .= '<tr bgcolor="white" valign="center">';
		/*$s .= "\n\t<td>";
		if ($perms->checkModuleItem($m, 'edit', $row['task_id']) ) {
			$s .= "\n\t\t<a href=\"?m=tasks&a=view&task_id=".$row['task_id']."&tab=1&task_log_id=".@$row['task_log_id']."\">"
				. "\n\t\t\t". dPshowImage( './images/icons/stock_edit-16.png', 16, 16, '' )
				. "\n\t\t</a>";
		}
		$s .= "\n\t</td>";*/
		
		$date1 = ($task_log_date ? $task_log_date->format( $df ) : '-');
	    $date2 = ($task_log_edit_date ?  $task_log_edit_date->format( $df ) : '-');
	    $date3 = ($task_log_start_date ?  $task_log_start_date->format( $df ) : '-');
	    $date4 = ($task_log_finish_date ? $task_log_finish_date->format( $df ) : '-');
	    $date = $date1." (C)";
	    if ($date2 != $date1 && $date2 != '-')
	    	$date .="<br>".$date2." (E)";
	    if (($date3 != $date1 && $date3 != '-') || ($date4 != $date1 && $date4 != '-')) {
	    	$date .="<br>".$date3." (S)";
	    	$date .="<br>".$date4." (F)";
	    }
	    $s .= '<td align="right">'.$row['task_log_progress'] . '%</td>';
	
		$s .= '<td style="'.$style2.'" nowrap>'.$date.'</td>';
		$s .= '<td nowrap>'.CTask::getWBS($row['task_id']).'</td>';
		$s .= '<td width="36%" style="'.$style.'"><a href="?m=tasks&a=view&task_id='.$row['task_id'].'&tab=0">'.@$row["task_log_name"].'</a></td>';
	    $s .= '<td>'.$row["user_username"].'</td>'; 
	    $s .= '<td>'.$row["proles_name"].'</td>';
		$s .= '<td width="80" align="right" nowrap>'.sprintf( "%.2f", $row["task_log_hours"] ) . ' ph</td>';
		$cr = $row["proles_hour_cost"]*$row["task_log_hours"];
		$s .= '<td width="80" align="right" nowrap>'.(float)($cr)." ".$dPconfig['currency_symbol'].'</td>';
		$s .= '<td>';
	
	// dylan_cuthbert: auto-transation system in-progress, leave these lines
		$transbrk = "\n[translation]\n";
		$descrip = str_replace( "\n", "<br />", $row['task_log_description'] );
		$tranpos = strpos( $descrip, str_replace( "\n", "<br />", $transbrk ) );
		if ( $tranpos === false) $s .= $descrip;
		else
		{
			$descrip = substr( $descrip, 0, $tranpos );
			$tranpos = strpos( $row['task_log_description'], $transbrk );
			$transla = substr( $row['task_log_description'], $tranpos + strlen( $transbrk ) );
			$transla = trim( str_replace( "'", '"', $transla ) );
			$s .= $descrip."<div style='font-weight: bold; text-align: right'><a title='$transla' class='hilite'>[".$AppUI->_("translation")."]</a></div>";
		}
	// end auto-translation code
				
		$s .= '</td>';
		$s .= "\n\t<td>";
		if ($canDelete) {
			$s .= "\n\t\t<a href=\"javascript:delIt2({$row['task_log_id']});\" title=\"".$AppUI->_('delete log')."\">"
				. "\n\t\t\t". dPshowImage( './images/icons/stock_delete-16.png', 16, 16, '' )
				. "\n\t\t</a>";
		}
		$s .= "\n\t</td>";
		$s .= '</tr>';
		$hrs += (float)$row["task_log_hours"];
		$crs += (float)$cr;
	}

	if(($logs[$i][task_log_creator]!=$logs[$i+1][task_log_creator])&&($user_id==-2))	{
		$s .= '<tr bgcolor="white" valign="top">';
		$s .= '<td colspan="5" align="right">' . $AppUI->_('Totals') .' for '.$row["user_username"]. ':</td>';
		$s .= '<td align="right" nowrap>' . sprintf( "%.2f", $hrs ) . ' ph</td>';
		$s .= '<td align="right" nowrap>' . sprintf( "%.2f", $crs ) ." ".$dPconfig['currency_symbol'].'</td>';
		$s .= '</tr>';
		$hrs=0;
		$crs=0;}
		$i++;
	}

if($user_id!=-2){
	$s .= '<tr bgcolor="white" valign="top">';
	$s .= '<td colspan="5" align="right">' . $AppUI->_('Totals') . ':</td>';
	$s .= '<td align="right" nowrap>' . sprintf( "%.2f", $hrs ) . ' ph</td>';
	$s .= '<td align="right" nowrap>' . sprintf( "%.2f", $crs ) ." ".$dPconfig['currency_symbol'].'</td>';
	$s .= '</tr>';}
echo $s;
?>
</table>
</div>