<?php

putenv('GDFONTPATH=' . realpath('../fonts/Droid'));
$font_bold = "DroidSans-Bold.ttf";
$font_normal = "DroidSans.ttf";

$font = $font_normal;
$font_size = 14;
$text = "TaskBox";

$multiplier = 1.0;
$bordersize = 1;
$minsize = array('w' => 0, 'h' => 0);
$maxsize = array('w' => 0, 'h' => 0);


function getTextSize($text) {
	global $font, $font_size;

	$txtbox = imagettfbbox($font_size, 0, $font, $text);
	$txtW = abs(max($txtbox[2], $txtbox[4])) + abs(max($txtbox[0], $txtbox[6]));
	$txtH = abs(max($txtbox[5], $txtbox[7])) + abs(max($txtbox[1], $txtbox[3]));


echo "$text = $txtW x $txtH\n";print_r($txtbox);echo "\n";


	return array('w' => $txtW, 'h' => $txtH, 'low' => $txtbox[3]/* + $txtbox[0] fixes guuuu*/);
}

$minsize = getTextSize("3.3.");
$minsize['w'] += intval(($minsize['w']/100) * 50);
$minsize['h'] += intval(($minsize['h']/100) * 50);
$maxsize['w'] = $minsize['w'] * 3;

function getFixedText($text, $maxlen /*$trim_type*/) {
	global $font, $font_size;

	// Always truncate the text... To be improved

	do {
		$text = substr($text, 0, -1);
		$tsize = getTextSize($text."...");
	}
	while ($tsize['w'] >= $maxlen && strlen($text) > 1);

	return $text."...";
}

function generateTextImg($text, $style = "normal", $decoration = null, $align = "left", $maxlen = 0) {
	global $font, $font_size;

	$txtimg = null;
	$vspace = intval($font_size * 20 / 100);
	$hspace = 0; // FIXME, bugged if > 0
	$line_size = array();
	$txtimg_size = null;

	$text_lines = explode("\n", $text);

	foreach ($text_lines as $line) {
		$lsize = getTextSize($line);

		if ($maxlen > 0 && $lsize['w'] >= $maxlen) {
			$line = getFixedText($line, $maxlen);
			$lsize = getTextSize($line);
		}

		$txtimg_size['w'] = max($txtimg_size['w'], $lsize['w']);
		$txtimg_size['h'] += $lsize['h'] + $vspace;
		$lsize['top'] = $txtimg_size['h']-$lsize['h'];

		$lines[] = $line;
		$line_size[] = $lsize;
	}

	$txtimg_size['w'] += $hspace*2;

	$txtimg = imagecreatetruecolor($txtimg_size['w'], $txtimg_size['h']);
	$background_color = imagecolorallocate($txtimg, 255, 255, 255);
	imagefilledrectangle($txtimg, 0, 0, $txtimg_size['w'], $txtimg_size['h'], $background_color);

	for ($i = 0; $i < count($lines); $i++) {
		$lsize = $line_size[$i];
		$text = $lines[$i];

		$timg = imagecreatetruecolor($txtimg_size['w'], $lsize['h']+1);
		//imageantialias($timg, true);
		imagefilledrectangle($timg, 0, 0, $txtimg_size['w'], $lsize['h'], $background_color);

		if ($align == "center") {
			$padding = intval(($txtimg_size['w'] - $lsize['w']) / 2);
		} else if ($align == "right") {
			$padding = $txtimg_size['w'] - $lsize['w'] - $hspace/2 + 1;
		} else /*($align == "left") */ {
			$padding = $hspace/2 - 1;
		}

		$font_color = imagecolorallocate($timg, 0, 0, 0);

		$txtX = $padding;
		$txtY = $font_size; //intval($font_size + (imagesy($timg) - $lsize['h'])/2);

		if ($lsize['h'] <= $font_size)
				$txtY -= ($font_size - $lsize['h']) + 1;

		// FIXME: afaasj at 14px
		imagettftext($timg, $font_size, 0, $txtX, $txtY, $font_color, $font, $text);

		if ($decoration == "underline") {
			// FIXME: text like "ggguuuu"
			$lineY = $lsize['h']-$lsize['low'];
			imageline($timg, $padding, $lineY, $padding + $lsize['w'] /*- $hspace*/, $lineY, $font_color);
			//imageline($timg, $padding, $lineY+1, $padding + $lsize['w'] - $hspace, $lineY+1, $font_color);
		}

		imagecopy($txtimg, $timg, 0, $lsize['top'], 0, 0, imagesx($timg), imagesy($timg));
		imagedestroy($timg);
	}

	return $txtimg;
}

function buildTextRectangle($text, $align = "left") {
	global $font, $font_size, $bordersize, $minsize, $maxsize, $size;

	$w = $size['w'];
	$h = $size['h'];
	$b = $bordersize; //intval($bordersize * $multiplier);

	$txt_size = getTextSize($text);
//echo $text."\n";print_r($txt_size); return;
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

	if ($txt_size['h'] < $font_size)
		$txtY -= ($font_size - $txt_size['h']) + 1;

	imagettftext($tbx, $font_size, 0, $txtX, $txtY, $font_color, $font, $text);

	return $tbx;
}

function buildImgRectangle($img, $padding = 0, $align = "left") {
	global $bordersize, $minsize, $maxsize, $size;

	$w = $size['w'];
	$h = $size['h'];
	$b = $bordersize; //intval($bordersize * $multiplier);

	if ($size['h'] == 0)
		$h = imagesy($img) + intval(imagesy($img) * 5 / 100); // * $multiplier

	$h += $padding*2 + $bordersize*2;
	$w += $padding*2 + $bordersize*2;

/*
		 print_r($txt_size); echo $w."x$h";
*/

	// Check bigger image!

	$tbx = imagecreate($w, $h);
//	$tbx = imagecreatetruecolor($w, $h);
//	imageantialias($tbx, true);

	$background_color = imagecolorallocate($tbx, 255, 255, 255); # = 0xFFFFFF
	$border_color = imagecolorallocate($tbx, 0, 0, 0); # = 0x000000

	imagefilledrectangle($tbx, 0, 0, $w-1, $h-1, $border_color);
	imagefilledrectangle($tbx, $b, $b, $w-$b-1, $h-$b-1, $background_color);

	if ($align == "center") {
		$imgX = intval((imagesx($tbx) - imagesx($img)) / 2);
	} else if ($align == "right") {
		$imgX = imagesx($tbx) - imagesx($img) - $bordersize - $padding;
	} else {
		$imgX = $bordersize + $padding;
	}

	$imgY = intval((imagesy($tbx) - imagesy($img)) / 2);

	imagecopy($tbx, $img, $imgX, $imgY, 0, 0, imagesx($img), imagesy($img));

	return $tbx;
}



//$size = $minsize;
//$tbx = buildTextRectangle("1.1");


$size = $maxsize;
$tbx = buildTextRectangle("1.1\nsafkjasf\nafgklkajg\nkhjsfahjakfjafa\n");
$tbx = buildTextRectangle("meeeeeeeee");
//getTextSize("1.1\nUUUU");
//getTextSize("1.1"); exit;

//$image = imagecreatetruecolor(420, 630);
//imagecopy($image, $tbx,0, 0, 0, 0, $w, $h);
//imagecopy($image, $tbx,220,330,0,0, $w, $h);
//$tbx = $image;

//$tbx = generateTextImg("Test\naasfa\nPoooo\nNuuuuu\nNooo\nMeee\nNu\nNuuuuuu\nBarababBab\nGggggA\n2009.10.22\nNA\n2/9");
$tbx = generateTextImg("Test\nGggggA\n2009.10.22\nNA\nmeeeeeeee\nFuuuuuuuuasgsauuasFaV\nafaasj\nph\nmmmmmmmmmmmmmmmmmmmmeeeeeee\nguuuuu",
						null, "underline", "left", $maxsize['w']);

$tbx = buildImgRectangle($tbx, 3, "left");


//$size = $minsize;
//$tbx = generateTextImg("1.1.");
//$tbx = buildImgRectangle($tbx, 3, "center");


header("Content-type: image/png");

imagepng($tbx);
imagedestroy($tbx);


//class ImgBox {
//	private $pBorder;
//	private $pFont;
//	private $pFontSize;
//	private $pPadding;
//	private $pMinSize;
//	private $pMaxSize;
//	private $img;
//
//	public function ImgBox($border_size, $font, $font_size, $padding/* $multiplier, $colors... */) {
//		$pBorder = $border_size;
//		$pFont = $font;
//		$pFontSize = $font_size;
//		$pPadding = $padding;
//	}
//
//}

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
/*
function calculateTextBox($text) {
	global $font, $font_size;

	$txtbox = imagettfbbox($font_size, 0, $font, $text);

    $min_x = min(array($txtbox[0], $txtbox[2], $txtbox[4], $txtbox[6]));
    $max_x = max(array($txtbox[0], $txtbox[2], $txtbox[4], $txtbox[6]));
    $min_y = min(array($txtbox[1], $txtbox[3], $txtbox[5], $txtbox[7]));
    $max_y = max(array($txtbox[1], $txtbox[3], $txtbox[5], $txtbox[7]));

	return array('w' => $max_x - $min_x, 'h' => $max_y - $min_y);

	//jpgraph H: return $bbox[1]-$bbox[5]+1;

    return array(
        'left' => ($min_x >= -1) ? -abs($min_x + 1) : abs($min_x + 2),
        'top' => abs($min_y),
        'width' => $max_x - $min_x,
        'height' => $max_y - $min_y,
        'box' => $box
    );

}
*/

?>