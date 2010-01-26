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

include "WBS.class.php";


ini_set('memory_limit', dPgetParam($dPconfig, 'reset_memory_limit', 8*1024*1024));

$project_id = 5;

// Open: progettazione
$tasks_opened = array(95);
// Closed: analisi and Organizzazione
$tasks_closed = array(79, 86, 212, 87, 92, 107, 124, 88, 274, 134, 316, 90, 135, 91, 97, 108, 139, 317, 115, 143, 177, 318, 116, 178, 258, 117, 179, 118);
$tasks_level = 2;

$wbs = new WBS($project_id);
$wbs->setOpenedTasks($tasks_opened);
$wbs->setClosedTasks($tasks_closed);
$wbs->setTaskLevel($tasks_level);
$wbs->showProgress(true);
$wbs->showAlerts(true);
$wbs->showNames(true);
$wbs->showPlannedData(true);
$wbs->showPlannedResources(true);
$wbs->showPlannedTimeframe(true);
$wbs->showActualData(true);
//$wbs->showActualResources(true);
$wbs->showActualTimeframe(true);
$wbs->draw();

ini_restore('memory_limit');

?>
