<?php
$baseDir = "../";
include "$baseDir/includes/config.php";
include "$baseDir/includes/db_adodb.php";
include "$baseDir/includes/db_connect.php";
include "$baseDir/includes/main_functions.php";

include "$baseDir/classes/ui.class.php";
if (!isset( $_SESSION['AppUI'] ) || isset($_GET['logout'])) {
    if (isset($_GET['logout']) && isset($_SESSION['AppUI']->user_id))
    {
        $AppUI =& $_SESSION['AppUI'];
		$user_id = $AppUI->user_id;
        addHistory('login', $AppUI->user_id, 'logout', $AppUI->user_first_name . ' ' . $AppUI->user_last_name);
    }

	$_SESSION['AppUI'] = new CAppUI;
}
$AppUI =& $_SESSION['AppUI'];
include "$baseDir/modules/tasks/tasks.class.php";

include "TaskBox.class.php";
include "TaskBoxDB.class.php";

include "$baseDir/lib/phptreegraph/GDRenderer.php";

/*
include "$baseDir/classes/ui.class.php";
if (!isset( $_SESSION['AppUI'] ) || isset($_GET['logout'])) {
    if (isset($_GET['logout']) && isset($_SESSION['AppUI']->user_id))
    {
        $AppUI =& $_SESSION['AppUI'];
		$user_id = $AppUI->user_id;
        addHistory('login', $AppUI->user_id, 'logout', $AppUI->user_first_name . ' ' . $AppUI->user_last_name);
    }

	$_SESSION['AppUI'] = new CAppUI;
}
$AppUI =& $_SESSION['AppUI'];
include "$baseDir/modules/tasks/tasks.class.php"; */

ini_set('memory_limit', dPgetParam($dPconfig, 'reset_memory_limit', 8*1024*1024));

$project_id = 5;
$query = "SELECT task_id, task_name, task_parent, task_start_date, task_finish_date FROM tasks t ".
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
foreach ($results as &$project) {
	$translate[$project['task_id']] = $id;
	$items[$id]['oldid'] = $project['task_id'];
	$items[$id]['id'] = $id;
	$items[$id]['name'] = $project['task_name'];
	$items[$id]['parent'] = isset($translate[$project['task_parent']]) ? $translate[$project['task_parent']] : 1;
	if ($items[$id]['parent'] == $id)
		$items[$id]['parent'] = 1;

	$tbdb = new taskBoxDB($project['task_id']);

	$tbx = new TaskBox($tbdb->getWBS());
	$tbx->setName($tbdb->getTaskName());
	$tbx->setProgress($tbdb->getProgress());
	$tbx->setPlannedDataArray($tbdb->getPlannedData());
	$tbx->setActualDataArray($tbdb->getActualData());
	$tbx->setPlannedTimeframeArray($tbdb->getPlannedTimeframe());
	$tbx->setActualTimeframeArray($tbdb->getActualTimeframe());
	$tbx->setResourcesArray($tbdb->getActualResources());
	$tbx->setAlerts($tbdb->isAlerted()); //FIXME change the position in wbs.

	$items[$id]['tbx'] = $tbx;
	unset($tbdb);

	$id++;
}
unset($results);
unset($translate);
//print_r($items);

$tbx = new TaskBox(null);
$tbx->setName("G3-sw4us");
$objTree->add(1, 0, "", $tbx->getWidth(), $tbx->getHeight(), $tbx->getImage());
foreach ($items as $item) {
	$objTree->add($item['id'], $item['parent'], '', $item['tbx']->getWidth(), $item['tbx']->getHeight(), $item['tbx']->getImage());
}

$objTree->setBGColor(array(255, 255, 255));
$objTree->setLinkColor(array(0, 0, 0));

include "$baseDir/lib/fpdf/fpdf.php";

function makeWBSPdf($im){
	$mode = 'L';
	$pdf=new FPDF($mode, 'mm', 'a4');

	$tmp = tempnam('.', 'wbs');
	imagepng($im, $tmp);

	$pdf->AddPage();

	// TODO center the image (vertically and horizontally)
	if (imagesx($im) > imagesy($im))  {
		$w = $pdf->w - $pdf->lMargin - $pdf->rMargin;
		$h = 0;
	} else {
		$w = 0;
		$h = $pdf->h - $pdf->tMargin - $pdf->bMargin;
	}

	$pdf->Image($tmp, null, null, $w, $h, 'png');
	echo $pdf->Output('wbs.pdf','S');

	unlink($tmp);
}

if (isset($_REQUEST['pdf'])) {
	makeWBSPdf($objTree->image());
} else {
//	echo "PEAK ".memory_get_peak_usage(). " ".memory_get_peak_usage(true)."<br>";
//	echo "MEM ".memory_get_usage(). " ".memory_get_usage(true);
	$objTree->stream();
}

ini_restore('memory_limit');

?>
