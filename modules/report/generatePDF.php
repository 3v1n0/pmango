<?php
include('makePDF.php');

function getProjectName($project_id) {
	$q  = new DBQuery;
	$q->addQuery('projects.project_name');
	$q->addTable('projects');
	$q->addWhere("project_id = $project_id ");
	$name = $q->loadList();
	
	return $name[0]['project_name'];
}

function deletePDF($project_id, $type) {
	global $AppUI;
	
	$pname = getProjectName($project_id);
	$file = PM_filenamePdf($pname, $type);
	@unlink($file);
	$AppUI->unsetSubState('PDFReports', $type);
}

function generateLogPDF($project_id, $user_id, $hide_inactive, $hide_complete, $start_date, $end_date) {
	global $AppUI;
	
	$pname = getProjectName($project_id);
	$pdf = PM_headerPdf($pname);
	PM_makeLogPdf($pdf, $project_id, $user_id, $hide_inactive, $hide_complete, $start_date, $end_date);
	$filename = PM_footerPdf($pdf, $pname, PMPDF_LOG);
	$AppUI->setSubState('PDFReports', PMPDF_LOG, $filename);
}

?>