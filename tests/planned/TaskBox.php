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
     "<img src='/TMP/".basename($tmp)."' /><br />\n";
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

?>

</body>
</html>