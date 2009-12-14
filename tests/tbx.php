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

function calculateTextBox($text) {
	global $font, $font_size;
	
	$txtbox = imagettfbbox($font_size, 0, $font, $text);

    $min_x = min(array($txtbox[0], $txtbox[2], $txtbox[4], $txtbox[6]));
    $max_x = max(array($txtbox[0], $txtbox[2], $txtbox[4], $txtbox[6]));
    $min_y = min(array($txtbox[1], $txtbox[3], $txtbox[5], $txtbox[7]));
    $max_y = max(array($txtbox[1], $txtbox[3], $txtbox[5], $txtbox[7]));

	return array('w' => $max_x - $min_x, 'h' => $max_y - $min_y);
	
	//jpgraph H: return $bbox[1]-$bbox[5]+1;
/*
    return array(
        'left' => ($min_x >= -1) ? -abs($min_x + 1) : abs($min_x + 2),
        'top' => abs($min_y),
        'width' => $max_x - $min_x,
        'height' => $max_y - $min_y,
        'box' => $box
    );
*/
}

function getTextSize($text) {
	global $font, $font_size;

	$txtbox = imagettfbbox($font_size, 0, $font, $text);
	$txtW = abs(max($txtbox[2], $txtbox[4])) + abs(max($txtbox[0], $txtbox[6]));
	$txtH = abs(max($txtbox[5], $txtbox[7])) + abs(max($txtbox[1], $txtbox[3]));

/*
echo "$text = $txtW x $txtH\n";print_r($txtbox);echo "\n";
*/

	return array('w' => $txtW, 'h' => $txtH);
}

putenv('GDFONTPATH=' . realpath('../fonts/Droid'));
$font_bold = "DroidSans-Bold.ttf";
$font_normal = "DroidSans.ttf";

$font = $font_bold;
$font_size = 15;
$text = "TaskBox";

$multiplier = 1.0;
$bordersize = 1;
$minsize = array('w' => 0, 'h' => 0);
$maxsize = array('w' => 0, 'h' => 0);

$minsize = getTextSize("3.3.");
$minsize['w'] += intval(($minsize['w']/100) * 50);
$minsize['h'] += intval(($minsize['h']/100) * 50);
$maxsize['w'] = $minsize['w'] * 3;

function generateTextImg($text, $style = "normal", $decoration = null, $align = "left", $maxlen = 0) {
	global $font, $font_size;
	
	$txtimg = null;
	$vspace = intval($font_size * 20 / 100);
	$hspace = 2;
	$line_size = array();
	$txtimg_size = null;
	
	$text_lines = explode("\n", $text);

	foreach ($text_lines as $line) {
		$lsize = getTextSize($line);
		$lsize['h']++;
		$lsize['w']++;
		
		$txtimg_size['w'] = max($txtimg_size['w'], $lsize['w']);
		$txtimg_size['h'] += $lsize['h'] + $vspace;
		$lsize['top'] = $txtimg_size['h']-$lsize['h'];
		
		$line_size[] = $lsize;
	}
	
	$txtimg = imagecreatetruecolor($txtimg_size['w'], $txtimg_size['h']);
	$background_color = imagecolorallocate($txtimg, 255, 255, 255);
	imagefilledrectangle($txtimg, 0, 0, $txtimg_size['w'], $txtimg_size['h'], $background_color);
	
	for ($i = 0; $i < count($text_lines); $i++) {
		$lsize = $line_size[$i];

		$timg = imagecreatetruecolor($lsize['w'], $lsize['h']);
		//imageantialias($timg, true);
		
		$font_color = imagecolorallocate($timg, 0, 0, 0);

		imagefilledrectangle($timg, 0, 0, $lsize['w'], $lsize['h'], $background_color);
		imagettftext($timg, $font_size, 0, $hspace, $font_size, $font_color, $font, $text_lines[$i]);

		if ($decoration == "underlined")
			imageline($timg, 2, $lsize['h']-1, $lsize['w'], $lsize['h']-1, $font_color);
			
		imagecopy($txtimg, $timg, 0, $lsize['top'], 0, 0, imagesx($timg), imagesy($timg));		
		imagedestroy($timg);
	}
	
	return $txtimg;
}

function buildRectangle($text) {
	global $font, $font_size, $bordersize, $minsize, $maxsize, $size;

	$w = $size['w'];
	$h = $size['h'];
	$b = $bordersize; //intval($bordersize * $multiplier);

	$txt_size = getTextSize($text);

	if ($size['h'] == 0)
		$h = $txt_size['h'] + intval($txt_size['h'] * 10 / 100); // * $multiplier
		
/*
		 print_r($txt_size); echo $w."x$h";
*/

	// Check bigger text!

	$tbx = imagecreate($w, $h);
//	$tbx = imagecreatetruecolor($w, $h);
//	imageantialias($tbx, true);

	$background_color = imagecolorallocate($tbx, 255, 255, 255); # = 0xFFFFFF
	$border_color = imagecolorallocate($tbx, 0, 0, 0); # = 0x000000
	$font_color = $border_color;

	imagefilledrectangle($tbx, 0, 0, $w-1, $h-1, $border_color);
	imagefilledrectangle($tbx, $b, $b, $w-$b-1, $h-$b-1, $background_color);

	$txtX = intval((imagesx($tbx) - $txt_size['w']) / 2);
	$txtY = intval($font_size + (imagesy($tbx) - $txt_size['h'])/2);

	imagettftext($tbx, $font_size, 0, $txtX, $txtY, $font_color, $font, $text);

	return $tbx;
}



$size = $minsize;
$tbx = buildRectangle("1.1");


$size = $maxsize;
//$tbx = buildRectangle("1.1\n1111111111111\n111111111\nmeeeeeee\n1BNuuufffu\nGoiunggg");
#$tbx = buildRectangle("1.1");

//$image = imagecreatetruecolor(420, 630);
//imagecopy($image, $tbx,0, 0, 0, 0, $w, $h);
//imagecopy($image, $tbx,220,330,0,0, $w, $h);
//$tbx = $image;

$tbx = generateTextImg("Test\naasfa\nPoooo\nNuuuuu\nNooo\nMeee\nNu\nNuuuuuu\nBarababBab\nGggggA\n2009.10.22\nNA\n2/9");
$tbx = generateTextImg("Test\nGggggA\n2009.10.22\nNA\n2/9", null, "underlined");

header("Content-type: image/png");

imagepng($tbx);
imagedestroy($tbx);
?>
