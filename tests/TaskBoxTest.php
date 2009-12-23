<?php
include "TaskBox.class.php";


/* BASE */
$tbx = new TaskBox("1.1.");
//$tbx->draw();

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
$tbx->setResources("22 ph, Dilbert, Requirement Engineering\n".
                   "14 ph, Wally, Sales Manager\n".
                   "4 ph, The Boss, Manager");

/* With Progress */
$tbx->setProgress(70);

$tbx->draw();


?>
