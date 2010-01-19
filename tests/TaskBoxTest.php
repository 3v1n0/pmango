<?php
include "TaskBox.class.php";


/* BASE */
$tbx = new TaskBox("1.1.");

/* With Expand sign */
$tbx->showExpand(true);

/* With name */
$tbx->setName("Test");

/* With Long name */
$tbx->setName("Requirement Analysis");

/* With Planned Data */
$tbx->setPlannedData("14 d", "40 ph", "1350 €");

/* With Actual Data */
$tbx->setActualData("4 d", "6 ph", "230 €");

/* With Planned Timeframe */
$tbx->setPlannedTimeframe("2009.10.15", "2009.10.29");

/* With Actual Timeframe */
$tbx->setActualTimeframe("2009.10.16", "NA");

/* With Resources */
$tbx->addResources("Dilbert", "Requirement Engineering", 22);
$tbx->addResources("Wally", "Sales Manager", 14);
$tbx->addResources("The Boss", "Manager", "04");

/* With Progress */
$tbx->setProgress(50);

/* With Alert */
$tbx->setAlerts(TaskBox::ALERT_ERROR);

$tbx->draw();

?>
