<?php
$baseDir = "../";
include "$baseDir/includes/config.php";
include "$baseDir/includes/db_adodb.php";
include "$baseDir/includes/db_connect.php";
include "$baseDir/includes/main_functions.php";

include "$baseDir/lib/phptreegraph/GDRenderer.php";
include "TaskBox.class.php";

include "$baseDir/lib/fpdf/fpdf.php";

$project_id = 5;
$query = "SELECT task_id, task_name, task_parent FROM tasks t ".
         "WHERE t.task_project = ".$project_id." ORDER BY task_id";

$result = db_exec($query);
$error = db_error();
if ($error) {
	echo $error;
	exit;
}
//$results = array();
for ($i = 0; $i < db_num_rows($result); $i++) {
	$results[] = db_fetch_assoc($result);
}

$objTree = new GDRenderer(30, 10, 30, 50, 20);
//$objTree->add(1,0,'/', 10);

$id = 2;
$translate = array();
foreach ($results as $project) {
	$translate[$project['task_id']] = $id;
	$items[$id]['oldid'] = $project['task_id'];
	$items[$id]['id'] = $id;
	$items[$id]['name'] = $project['task_name'];
	$items[$id]['parent'] = isset($translate[$project['task_parent']]) ? $translate[$project['task_parent']] : 1;
	if ($items[$id]['parent'] == $id)
		$items[$id]['parent'] = 1;
	$tbx = new TaskBox($id);
	$tbx->setName($project['task_name']);
	$tbx->setProgress(rand(0, 100));
	$tbx->setPlannedData("14 d", "40 ph", "1350 €");
	$tbx->setActualData("4 d", "6 ph", "230 €");
	$tbx->setPlannedTimeframe("2009.10.15", "2009.10.29");
	$tbx->setActualTimeframe("2009.10.16", "NA");

	$items[$id]['tbx'] = $tbx;

	$id++;
}
//print_r($items);
$tbx = new TaskBox(null);
$tbx->setName("G3-sw4us");
$objTree->add(1, 0, "", imagesx($tbx->getImage()), imagesy($tbx->getImage()), $tbx->getImage());
foreach ($items as $item) {
	$objTree->add($item['id'], $item['parent'], "", imagesx($item['tbx']->getImage()), imagesy($item['tbx']->getImage()), $item['tbx']->getImage());
}

$objTree->setBGColor(array(255, 255, 255));
$objTree->setLinkColor(array(0, 0, 0));

function makeWBSPdf($im){
	$pdf=new FPDF('P', 'mm', 'a4');
	
	$tmp = tempnam('.','wbs').".png";
	imagepng($im, $tmp);
	
	$format = $pdf->_getpageformat('a4');
	
	if (imagesx($im) > imagesy($im))  {
		$w = $format[0] - $pdf->lMargin - $pdf->rMargin;
		$h = 0;
	} else {
		$w = 0;
		$h = $format[1] - $pdf->tMargin - $pdf->bMargin;
	}
	
	$pdf->AddPage();
	$pdf->Image($tmp, null, null, $w, $h);
	echo $pdf->Output('wbs.pdf','S');

	unlink($tmp);
}

if (isset($_REQUEST['pdf'])) {
	makeWBSPdf($objTree->image());
} else {
	$objTree->stream();
}

?>
