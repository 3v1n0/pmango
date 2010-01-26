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

ini_set('memory_limit', dPgetParam($dPconfig, 'reset_memory_limit', 8*1024*1024));

$project_id = 5;


// Open: progettazione
$tasks_opened = array(95);
// Closed: analisi and Organizzazione
$tasks_closed = array(79, 86, 212, 87, 92, 107, 124, 88, 274, 134, 316, 90, 135, 91, 97, 108, 139, 317, 115, 143, 177, 318, 116, 178, 258, 117, 179, 118);
$tasks_level = 2;

//$tasks_opened = array();
//$tasks_closed = array();
//$tasks_level = 4;


$query = "SELECT task_id, task_parent FROM tasks t ".
         "WHERE t.task_project = ".$project_id." ORDER BY task_id, task_parent, task_wbs_index";

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
$tbx = new TaskBox(null);
$tbx->setName("G3-sw4us");
$objTree->add(1, 0, "", $tbx->getWidth(), $tbx->getHeight(), $tbx->getImage());

$id = 2;
$translate = array();
foreach ($results as $task) {
	
	$add = false;
	
	//$level = CTask::getTaskLevel($task['task_id']);
	    
	if ($task["task_id"] == $task["task_parent"])
			$add = true;

	if (CTask::getTaskLevel($task["task_id"]) <= $tasks_level &&
		!in_array($task["task_id"], $tasks_closed) ||
	    in_array($task["task_id"], $tasks_closed) &&
	    !in_array($task["task_parent"], $tasks_closed) &&
	    !CTask::isLeafSt($task["task_id"])) {
		   $add = true;
	}

	if (@in_array($task["task_id"], $tasks_opened) ||
		@in_array($task["task_parent"], $tasks_opened))
		$add = true;
		
	if ($add) {
		$tbxdb = new taskBoxDB($task['task_id']);
		$wbs = $tbxdb->getWBS();
		$translate[$task['task_id']] = $id;
		
		$items[$wbs]['task_id'] = $task['task_id'];
		$items[$wbs]['task_parent'] = $task['task_parent'];
		$items[$wbs]['tbxdb'] = $tbxdb;
		$items[$wbs]['id'] = $id;

		$id++;
	}
}

unset($project);
unset($results);

ksort($items);
$tasks_opened = $AppUI->getState("tasks_opened");

foreach ($items as $item) {
	$parent = 1;
	
	if ($item['task_parent'] != $item['task_id'] && $item['task_parent'] > 1)
		$parent = $translate[$item['task_parent']];
		
	$tbdb = $item['tbxdb'];

	$tbx = new TaskBox($tbdb->getWBS());
//	$tbx->setName($tbdb->getTaskName());
//	$tbx->setProgress($tbdb->getProgress());
//	$tbx->setPlannedDataArray($tbdb->getPlannedData());
//	$tbx->setActualDataArray($tbdb->getActualData());
//	$tbx->setPlannedTimeframeArray($tbdb->getPlannedTimeframe());
//	$tbx->setActualTimeframeArray($tbdb->getActualTimeframe());
//	$tbx->setResourcesArray($tbdb->getActualResources());
	$tbx->setAlerts($tbdb->isAlerted()); //FIXME change the position in wbs.
	
	$objTree->add($item['id'], $parent, '', $tbx->getWidth(), $tbx->getHeight(),
	              $tbx->getImage(), 1-intval($tbx->getAlertSize()/2), intval($tbx->getAlertSize()/2));
}

unset($translate);

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
