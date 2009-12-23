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

$tbx->draw();


?>