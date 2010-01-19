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

include "TaskBoxDB.class.php";

//////////////////// Test classe ////////////////////

$tdb = new TaskBoxDB(135);
echo $tdb->getWBS()."\n";
echo $tdb->getTaskName()."\n";
print_r($tdb->getPlannedData());
print_r($tdb->getActualData());
print_r($tdb->getPlannedTimeframe());
print_r($tdb->getActualTimeframe());
print_r($tdb->getPlannedResources());
print_r($tdb->getActualResources());
echo $tdb->getProgress()."\n";
echo $tdb->isAlerted();
?>
