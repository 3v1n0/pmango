<?php
$baseDir = "../";
include "$baseDir/includes/config.php";
include "$baseDir/includes/db_adodb.php";
include "$baseDir/includes/db_connect.php";
include "$baseDir/includes/main_functions.php";

include "$baseDir/lib/phptreegraph/GDRenderer.php";
include "TaskBox.class.php";

include "$baseDir/lib/fpdf/fpdf.php";

$project_id = 5;
$query = "SELECT task_id, task_name, task_parent FROM tasks t ".
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
foreach ($results as $project) {
	$translate[$project['task_id']] = $id;
	$items[$id]['oldid'] = $project['task_id'];
	$items[$id]['id'] = $id;
	$items[$id]['name'] = $project['task_name'];
	$items[$id]['parent'] = isset($translate[$project['task_parent']]) ? $translate[$project['task_parent']] : 1;
	if ($items[$id]['parent'] == $id)
		$items[$id]['parent'] = 1;

	$tbx = new TaskBox($id);
	$tbx->setName($project['task_name']);
	$tbx->setProgress(rand(0, 100)); //FIXME una tbx con valore di completamento uguale a 0 non visualizza la sua barra

	$items[$id]['tbx'] = $tbx;

	$id++;
}
//print_r($items);
$tbx = new TaskBox("G3-sw4us");
$objTree->add(1, 0, "", imagesx($tbx->getImage()), imagesy($tbx->getImage()), $tbx->getImage());
foreach ($items as $item) {
	$objTree->add($item['id'], $item['parent'], "", imagesx($item['tbx']->getImage()), imagesy($item['tbx']->getImage()), $item['tbx']->getImage());
}

$objTree->setBGColor(array(255, 255, 255));
$objTree->setNodeColor(array(0, 0, 0));
$objTree->setLinkColor(array(0, 0, 0));
//$objTree->setNodeLinks(GDRenderer::LINK_BEZIER);
$objTree->setNodeBorder(array(0, 128, 255), 2);
//$objTree->setFTFont('./fonts/Vera.ttf', 10, 0, GDRenderer::CENTER|GDRenderer::TOP);

function makeWBSPdf($im){

//	$graph = //riceve il file da gantt.php;

	// Put the image in a PDF page
	//$im = $graph->Stroke(_IMG_HANDLER);

	$pdf = pdf_new();
	pdf_open_file($pdf, '');

	$pimg = pdf_open_memory_image($pdf, $im);

	pdf_begin_page($pdf, 595, 842);
	pdf_add_outline($pdf, 'Page 1');
	pdf_place_image($pdf, $pimg, 0, 500, 1);
	pdf_close_image($pdf, $pimg);
	pdf_end_page($pdf);
	pdf_close($pdf);

	$buf = pdf_get_buffer($pdf);
	$len = strlen($buf);

	// Send PDF mime headers
	header('Content-type: application/pdf');
	header("Content-Length: $len");
	header("Content-Disposition: inline; filename=foo.pdf");

	// Send the content of the PDF file da sostituire con la richiesta di salvataggio
	echo $buf;

	// .. and clean up
	pdf_delete($pdf);


}

if (isset($_REQUEST['pdf'])) {
	makeWBSPdf($objTree->image());
} else {
	$objTree->stream();
}

?>
