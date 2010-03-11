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

function getGroupName($project_id) {
	$q  = new DBQuery;
	$q->addTable('groups');
	$q->addTable('projects');
	$q->addQuery('groups.group_name');
	$q->addWhere("projects.project_group = groups.group_id and projects.project_id = $project_id");
	$group = $q->loadResult();
	
	return $group;
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

function getUserPDF($project_id, $type) {
	$pname = getProjectName($project_id);
	return PM_filenamePdf($pname, $type);
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
		$types = array(PMPDF_REPORT, PMPDF_ACTUAL, PMPDF_LOG, PMPDF_PLANNED, PMPDF_PROPERTIES,
		               PMPDF_GRAPH_GANTT, PMPDF_GRAPH_TN, PMPDF_GRAPH_WBS);
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
	
	$pdf = PM_headerPdf($pname, 'P', 1, null, null, PMPDF_LOG);
	PM_makeLogPdf($pdf, $project_id, $user_id, $hide_inactive, $hide_complete, $start_date, $end_date);
	$filename = PM_footerPdf($pdf, $pname, PMPDF_LOG);
	setProjectSubState('PDFReports', PMPDF_LOG, $filename);
	
	return $filename;
}

function generateTasksPDF($project_id, $tview, $task_level, $tasks_closed, $tasks_opened, $roles,
                          $start_date, $end_date, $showIncomplete, $showMine) {

	$name = getProjectName($project_id);
	$group_name = getGroupName($project_id);
	$type = $tview ? PMPDF_ACTUAL : PMPDF_PLANNED;

	$pdf = PM_headerPdf($name, 'P', 1, $group_name, null, $type);
	PM_makeTaskPdf($pdf, $project_id, $tview, $task_level, $tasks_closed, $tasks_opened,
	               $roles, $start_date, $end_date, $showIncomplete, $showMine);
	$filename = PM_footerPdf($pdf, $name, $type);
	setProjectSubState('PDFReports', $type, $filename);
	
	return $filename;
}

function generatePropertiesPDF($project_id, $properties, $page = 'P') {

	$name = getProjectName($project_id);

	$pdf = PM_headerPdf($name, 'P', 1, null, null, PMPDF_PROPERTIES);
	PM_makePropPdf($pdf, $project_id, $properties, $page);
	
	$filename = PM_footerPdf($pdf, $name, PMPDF_PROPERTIES);
	setProjectSubState('PDFReports', PMPDF_PROPERTIES, $filename);
	
	return $filename;
}

function generateGraphPDF($project_id, PMGraph $graph) {

	$name = getProjectName($project_id);
	
	switch ($graph->getType()) {
		case "TaskNetwork":
			$type = PMPDF_GRAPH_TN;
			break;
		case "WBS":
			$type = PMPDF_GRAPH_WBS;
			break;
		case "GANTT":
			$type = PMPDF_GRAPH_GANTT;
			break;
		default:
			return null;
	}
	
	if ($graph->getWidth() > $graph->getHeight())
		$page = 'L';
	else
		$page = 'P';

	$pdf = PM_headerPdf($name, $page, 1, null, null, $type);

	PM_makeGraphPDF($pdf, $graph, true);
	$filename = PM_footerPdf($pdf, $name, $type);
	setProjectSubState('PDFReports', $type, $filename);
	
	return $filename;
}


?>