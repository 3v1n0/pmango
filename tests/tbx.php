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


//echo "$text = $txtW x $txtH\n";print_r($txtbox);echo "\n";


	return array('w' => $txtW, 'h' => $txtH, 'low' => $txtbox[3]/* + $txtbox[0] fixes guuuu*/);
}

$minsize = getTextSize("3.3.");
$minsize['w'] += intval(($minsize['w']/100) * 50);
$minsize['h'] += intval(($minsize['h']/100) * 50);
$maxsize['w'] = $minsize['w'] * 3;

function getFixedText($text, $maxlen /*$trim_type*/) {
	global $font, $font_size;

	$tsize = getTextSize(strip_tags($text));

	if ($tsize['w'] < $maxlen)
		return $text;

	while ($tsize['w'] >= $maxlen && strlen($text) > 1) {
		$cut = -1;

		if ($tsize['w'] > $maxlen*2 + 2)
			$cut = strlen($text)/2;

		$text = substr($text, 0, $cut);
		$tsize = getTextSize(strip_tags($text)."...");
	}

	return $text."...";
}

function getTextBlock($text, $style = "normal", $align = "left", $maxlen = 0) {
	global $font, $font_size;

	$txtimg = null;
	$vspace = intval($font_size * 20 / 100);
	$hspace = 0; // FIXME, bugged if > 0
	$line_size = array();
	$txtimg_size = null;

	foreach (explode("\n", $text) as $line) {
		$stripped_line = strip_tags($line);
		$lsize = getTextSize($stripped_line);

		if ($maxlen > 0 && $lsize['w'] >= $maxlen) {
			$line = getFixedText($line, $maxlen);
			$stripped_line = strip_tags($line);
			$lsize = getTextSize($stripped_line);
		}

		$pre_end = 0;
		do {
			$start = strpos($line, "<u>", $pre_end);
			$end = @strpos($line, "</u>", $pre_end+1);

			if ($start !== false) {
				if ($end === false)
					$end = strlen($line);

				if ($start == 0) {
					$underline['start'] = 0;
				} else {
					$tsize = getTextSize(strip_tags(substr($line, 0, $start)));
					$underline['start'] = $tsize['w'];
					
					// XXX code below tries to fix the underline pos
//					$pre_size = getTextSize(strip_tags(substr($line, 0, $start)));
//					$part_size = getTextSize(substr($line, $start+3, $end-3-$start));
//					$full_size = getTextSize(strip_tags(substr($line, 0, $end)));
//					
//					$underline['start'] += $full_size['w'] - ($pre_size['w'] + $part_size['w']);

//					//echo "checking part [$start - $end]:".substr($line, $start+3, $end-3-$start)."\n";
//					//print_r($part_size);
//					//echo " vs full: [0 - $end]".strip_tags(substr($line, 0, $end))." | ".substr($line, 0, $end)."\n";
//					//print_r($full_size);
//					//echo " vs pre [0 - $start]:".strip_tags(substr($line, 0, $start))." | ".substr($line, 0, $start)."\n";
//					//print_r($pre_size);
//					//echo "\nStandard pos: ${underline['start']}";
				}

				$tsize = getTextSize(substr($line, $start+3, $end-3-$start));
				$underline['end'] = $underline['start']+$tsize['w']-3;

				$lsize['u'][] = $underline;
				$pre_end = $end;
			}
		} while ($start !== false);

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
		$text = strip_tags($lines[$i]);

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

		if (isset($lsize['u'])) {
			// FIXME: text like "ggguuuu"
			// imageline($timg, $padding, $lineY, $padding + $lsize['w'] /*- $hspace*/, $lineY, $font_color);

			$lineY = $lsize['h']-$lsize['low'];
			foreach ($lsize['u'] as $underlined)
				imageline($timg, $padding + $underlined['start'], $lineY, $padding + $underlined['end'] /*- $hspace*/, $lineY, $font_color);
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

	// imagedestroy($img) ??

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

//$tbx = getTextBlock("Test\naasfa\nPoooo\nNuuuuu\nNooo\nMeee\nNu\nNuuuuuu\nBarababBab\nGggggA\n2009.10.22\nNA\n2/9");
$tbx = getTextBlock("T<u>e</u>sta<u>aaaaaaaaaaaa</u>\nGg<u>gg</u>gA\n20<u>09.10</u>.22\nNA\n<u>m</u>ee<u>e</u>eeeee\nF<u>u</u>uuuuuuuasgsauuasFaV\nafaasj\nph\n<u>mmmmmmmmmmmmmmmmmmmmeeeeeee</u>\nguuuuu",
						null, "center", $maxsize['w']);

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