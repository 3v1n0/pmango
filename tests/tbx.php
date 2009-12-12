<?php

//include ("../lib/jpgraph/src/jpgraph.php");


//// Setup the graph
//$graph = new Graph(400,300);
//$graph->title->Set('Test');
//$graph->SetScale("intlin");
//
//$l1 = new LinePlot(array("h", "f"));
//$graph->Add($l1);
//
//$graph->Stroke();

/*
   function drawRating($rating) {
   $width = $_GET['width'];
   $height = $_GET['height'];
   if ($width == 0) {
      $width = 102;
   }
   if ($height == 0) {
      $height = 10;
   }

   //$rating = $_GET['rating'];
   $ratingbar = (($rating/100)*$width)-2;

   $image = imagecreate($width,$height);
   //colors
   $back = ImageColorAllocate($image,255,255,255);
   $border = ImageColorAllocate($image,0,0,0);
   $red = ImageColorAllocate($image,255,60,75);
   $fill = ImageColorAllocate($image,44,81,150);

   ImageFilledRectangle($image,0,0,$width-1,$height-1,$back);
   ImageFilledRectangle($image,1,1,$ratingbar,$height-1,$fill);
   ImageRectangle($image,0,0,$width-1,$height-1,$border);
   imagePNG($image);
   imagedestroy($image);
}minsize

Header("Content-type: image/png");
drawRating(10);
 */

function getTextSize($text) {
	global $font, $font_size;

	$txtbox = imagettfbbox($font_size, 0, $font, $text);
//	echo "$text\n";
//	print_r($txtbox);
	$txtW = abs(max($txtbox[2], $txtbox[4])) + abs(max($txtbox[0], $txtbox[6]));
	$txtH = abs(max($txtbox[5], $txtbox[7])) + abs(max($txtbox[1], $txtbox[3]));

	return array('w' => $txtW, 'h' => $txtH);
}

putenv('GDFONTPATH=' . realpath('../fonts/Droid'));
$font = "DroidSans.ttf";
$font_size = 10;
$text = "TaskBox";

$multiplier = 1.0;
$bordersize = 1;
$minsize = array('w' => 0, 'h' => 0);
$maxsize = array('w' => 0, 'h' => 0);

$minsize = getTextSize("3.3.");
$minsize['w'] += ($minsize['w']/100) * 50;
$minsize['h'] += ($minsize['h']/100) * 50;
$maxsize['w'] = $minsize['w'] * 3;


function buildRectangle($text) {
	global $font, $font_size, $bordersize, $minsize, $maxsize, $size;

	$w = $size['w'];
	$h = $size['h'];
	$b = $bordersize; //intval($bordersize * $multiplier);

	$txt_size = getTextSize($text);
	print_r($txt_size);

	if ($size['h'] == 0)
		$h = $txt_size['h'];

	// Check bigger text!

	$tbx = imagecreate($w, $h);
//	$tbx = imagecreatetruecolor($w, $h);
//	imageantialias($tbx, true);

	$background = imagecolorallocate($tbx, 255, 255, 255); # = 0xFFFFFF
	$border = imagecolorallocate($tbx, 0, 0, 0); # = 0x000000
	imagefilledrectangle($tbx, 0, 0, $w-1, $h-1, $border);
	imagefilledrectangle($tbx, $b, $b, $w-$b-1, $h-$b-1, $background);

	$txtX = intval((imagesx($tbx) - $txt_size['w']) / 2);
	$txtY = intval((imagesy($tbx) + $txt_size['h']) / 2) - 1;

	imagettftext($tbx, $font_size, 0, $txtX, $txtY, $border, $font, $text);

	return $tbx;
}

//$size = $minsize;
//$tbx = buildRectangle("1.1");

$size = $maxsize;
$tbx = buildRectangle("1.1\nsafkjasf\nafgklkajg\nkhjsfahjakfjafa\n");
//getTextSize("1.1\nUUUU");
//getTextSize("1.1"); exit;

//$image = imagecreatetruecolor(420, 630);
//imagecopy($image, $tbx,0, 0, 0, 0, $w, $h);
//imagecopy($image, $tbx,220,330,0,0, $w, $h);
//$tbx = $image;

header("Content-type: image/png");

imagepng($tbx);
imagedestroy($tbx);
?>