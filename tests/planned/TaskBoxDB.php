<?php
$baseDir = "../../";
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

include "../TaskBoxDB.class.php";

//////////////////// Test classe ////////////////////

$tdb = new TaskBoxDB(86);
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

echo "<br><h1>1.3.1.1 </h1><br>";
echo $tdb->getTaskName()."<br>";

echo "<br><h1>1.3.1.2</h1><br>";
$array = $tdb->getPlannedTimeframe();
echo "inizio:    ".$array['start']."  fine:  ".$array['end'];

echo "<br><h1>1.3.1.3</h1><br>";
$array = $tdb->getActualTimeframe();
echo "inizio:    ".$array['start']."  fine:  ".$array['end'];

echo "<br><h1>1.3.1.4</h1><br>";
$array = $tdb->getPlannedData();
echo "durata pianificata:  ".$array['duration']."  effort pianificato:  ".$array['effort']."  budget pianificato:  ".$array['cost'];

echo "<br><h1>1.3.1.5</h1><br>";
$array = $tdb->getActualData();
echo "durata attuale:  ".$array['duration']."  effort attuale:  ".$array['effort']."  budget attuale:  ".$array['cost'];


echo "<br><h1>1.3.1.6</h1><br>";
$array = $tdb->getPlannedResources();
foreach ($array as $stamp ) {
echo "nome:  ".$stamp['name']."  ruolo:  ".$stamp['role']."  effort pianificato:  ".$stamp['planned_effort'].'<br>';
}

echo "<br><h1>1.3.1.7</h1><br>";
$array = $tdb->getActualResources();
foreach ($array as $stamp ) {
echo "nome:  ".$stamp['name']."  ruolo:  ".$stamp['role']."  effort attuale:  ".$stamp['actual_effort'].'<br>';
}

echo "<br><h1>1.3.1.8</h1><br>";
$array= $tdb->getProgress();
echo $array;
?>
