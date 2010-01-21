<html>
	<head><title>Prove sulle TaskBox</title>
</head>
<body>

<?php

include "../TaskBox.class.php";
$font_path = "../../fonts/Droid";

function outTest($tbx, $id) {
	$tmp = tempnam("../../../TMP", "test");
	imagepng($tbx->getImage(), $tmp);
	echo "<h1>Prova $id</h1>\n".
     "<img src='/TMP/".basename($tmp)."' /><br />".
	 $tbx->getWidth()." x ".$tbx->getHeight()."<br />\n";
}

/* BASE */
$tbx = new TaskBox("1.1.");
$tbx->setFontPath($font_path);
outTest($tbx, "1.1.1.1");

$tbx->setAlerts(TaskBox::ALERT_ERROR);
outTest($tbx, "1.1.1.2.1");

$tbx->setAlerts(TaskBox::ALERT_WARNING);
outTest($tbx, "1.1.1.2.2");

$tbx->setAlerts(null);
$tbx->setName("Prova del nome");
outTest($tbx, "1.1.1.3");

$tbx->setPlannedData("20 d", "60 ph", "2000 €");
outTest($tbx, "1.1.1.4");

$tbx->setPlannedTimeframe("21/12/2009","11/2/2010");
$tbx->addResources("Andrea Toccafondi","Test enginee", "23");
$tbx->addResources("Francesco Purpura","Test enginee", "45");
$tbx->addResources("Marco Trevisa","Test enginee", "60");
$tbx->addResources("Matteo Pratesi","Test enginee", "20");
outTest($tbx, "1.1.1.5");

$tbx = new TaskBox("1.3.");
$tbx->setFontPath($font_path);
$tbx->setAlerts(TaskBox::ALERT_ERROR);
$tbx->setActualTimeframe("10/08/2009","11/11/2009");
$tbx->setProgress(50);
outTest($tbx, "1.1.1.6.1");

$tbx->setAlerts(null);
$tbx->setAlerts(TaskBox::ALERT_WARNING);
outTest($tbx, "1.1.1.6.2");

$tbx->setAlerts(null);
$tbx->setAlerts(TaskBox::ALERT_ERROR);
$tbx->setPlannedTimeframe("21/12/2009","11/2/2010");
$tbx->setActualTimeframe("10/08/2009","11/11/2009");
$tbx->addResources("Andrea Toccafondi","Test enginee", "23");
$tbx->addResources("Francesco Purpura","Test enginee", "45");
$tbx->addResources("Marco Trevisa","Test enginee", "60");
$tbx->addResources("Matteo Pratesi","Test enginee", "20");
$tbx->addResources("Andrea Toccafondi","Test enginee", "23", "30");
$tbx->addResources("Francesco Purpura","Test enginee", "23", "30");
$tbx->addResources("Marco TRevisan","Test enginee", "23", "30");
$tbx->addResources("Matteo Pratesi","Test enginee", "23", "30");
outTest($tbx, "1.1.1.7.1");

$tbx->setAlerts(null);
$tbx->setAlerts(TaskBox::ALERT_WARNING);
outTest($tbx, "1.1.1.7.2");

$tbx = new TaskBox("1.1.");
$tbx->setFontPath($font_path);
$tbx->showExpand(true);
outTest($tbx, "1.1.1.8");
?>

</body>
</html>