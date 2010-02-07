<?php
include_once('makePDF.php');

function getProjectName($project_id) {
	$q  = new DBQuery();
	$q->addQuery('projects.project_name');
	$q->addTable('projects');
	$q->addWhere("project_id = $project_id");
	$name = $q->loadResult();
	
	return $name;
}

function getUserProjects($user_id) {
	
	$q  = new DBQuery();
	$q->addQuery('project_id');
	$q->addTable('user_projects');
	$q->addWhere('user_id = '.$user_id);
	$q->addGroup('project_id');
	$res = $q->loadList();
	
	$ret = array();
	
	foreach (@$res as $result)
		$ret[] = $result['project_id'];

	return $ret;
}

function deletePDF($project_id, $type) {
	global $AppUI;
	
	$pname = getProjectName($project_id);
	$file = PM_filenamePdf($pname, $type);
	if (file_exists($file)) @unlink($file);
	unsetProjectSubState('PDFReports', $type);
}

function purgeUserPDFs($user_id) {
	global $AppUI;
	
	foreach (getUserProjects($user_id) as $pid) {
		$pname = getProjectName($pid);
		$types = array(PMPDF_ACTUAL, PMPDF_LOG, PMPDF_PLANNED, PMPDF_PROPERTIES, PMPDF_REPORT);
		foreach ($types as $type) {
			$file = PM_filenamePdf($pname, $type, $user_id);
			if (file_exists($file)) @unlink($file);
		}
	}
	
	if(isset($AppUI))
		unsetProjectsStates();
}

function generateLogPDF($project_id, $user_id, $hide_inactive, $hide_complete, $start_date, $end_date) {

	$pname = getProjectName($project_id);
	
	$pdf = PM_headerPdf($pname);
	PM_makeLogPdf($pdf, $project_id, $user_id, $hide_inactive, $hide_complete, $start_date, $end_date);
	$filename = PM_footerPdf($pdf, $pname, PMPDF_LOG);
	setProjectSubState('PDFReports', PMPDF_LOG, $filename);
}

function generateTasksPDF($project_id, $tview, $task_level, $tasks_closed, $tasks_opened, $roles,
                          $start_date, $end_date, $showIncomplete, $showMine) {

	$name = getProjectName($project_id);
	$type = $tview ? PMPDF_ACTUAL : PMPDF_PLANNED;

	$pdf = PM_headerPdf($name, 'P', 1, $group_name);
	PM_makeTaskPdf($pdf, $project_id, $task_level, $tasks_closed, $tasks_opened, $roles,
	               $tview, $start_date, $end_date, $showIncomplete, $showMine);
	$filename = PM_footerPdf($pdf, $name, $type);
	setProjectSubState('PDFReports', $type, $filename);
}

?>