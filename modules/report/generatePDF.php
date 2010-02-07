<?php
include_once('makePDF.php');

function getProjectName($project_id) {
	$q  = new DBQuery();
	$q->addQuery('projects.project_name');
	$q->addTable('projects');
	$q->addWhere("project_id = $project_id ");
	$name = $q->loadList();
	
	return $name[0]['project_name'];
}

function getUserProjects() {
	global $AppUI;
	
	$q  = new DBQuery();
	$q->addQuery('project_id');
	$q->addTable('user_projects');
	$q->addWhere('user_id = '.$AppUI->user_id);
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

function purgeUserPDFs() {
	global $AppUI;
	
	foreach (getUserProjects() as $pid) {
		$pname = getProjectName($pid);
		$types = array(PMPDF_ACTUAL, PMPDF_LOG, PMPDF_PLANNED, PMPDF_PROPERTIES, PMPDF_REPORT);
		foreach ($types as $type) {
			$file = PM_filenamePdf($pname, $type);
			if (file_exists($file)) @unlink($file);
		}
	}
}

function generateLogPDF($project_id, $user_id, $hide_inactive, $hide_complete, $start_date, $end_date) {
	global $AppUI;
	getUserProjects();
	$pname = getProjectName($project_id);
	$pdf = PM_headerPdf($pname);
	PM_makeLogPdf($pdf, $project_id, $user_id, $hide_inactive, $hide_complete, $start_date, $end_date);
	$filename = PM_footerPdf($pdf, $pname, PMPDF_LOG);
	setProjectSubState('PDFReports', PMPDF_LOG, $filename);
}

?>