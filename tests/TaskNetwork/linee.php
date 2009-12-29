<?
Header("Content-Type: image/jpeg");

include ("TaskNetwork.php");

function imageboldline($image, $x1, $y1, $x2, $y2, $color, $thick = 1)
{
   /* this way it works well only for orthogonal lines
   imagesetthickness($image, $thick);
   return imageline($image, $x1, $y1, $x2, $y2, $color);
   */
   if ($thick == 1) {
       return imageline($image, $x1, $y1, $x2, $y2, $color);
   }
   $t = $thick / 2 - 0.5;
   if ($x1 == $x2 || $y1 == $y2) {
       return imagefilledrectangle($image, round(min($x1, $x2) - $t), round(min($y1, $y2) - $t), round(max($x1, $x2) + $t), round(max($y1, $y2) + $t), $color);
   }
   $k = ($y2 - $y1) / ($x2 - $x1); //y = kx + q
   $a = $t / sqrt(1 + pow($k, 2));
   $points = array(
       round($x1 - (1+$k)*$a), round($y1 + (1-$k)*$a),
       round($x1 - (1-$k)*$a), round($y1 - (1+$k)*$a),
       round($x2 + (1+$k)*$a), round($y2 - (1-$k)*$a),
       round($x2 + (1-$k)*$a), round($y2 + (1+$k)*$a),
   );    
   imagefilledpolygon($image, $points, 4, $color);
   return imagepolygon($image, $points, 4, $color);
}


function arrow($im, $x1, $y1, $x2, $y2, $alength, $awidth, $color) {
    $distance = sqrt(pow($x1 - $x2, 2) + pow($y1 - $y2, 2));

    $dx = $x2 + ($x1 - $x2) * $alength / $distance;
    $dy = $y2 + ($y1 - $y2) * $alength / $distance;

    $k = $awidth / $alength;

    $x2o = $x2 - $dx;
    $y2o = $dy - $y2;

    $x3 = $y2o * $k + $dx;
    $y3 = $x2o * $k + $dy;

    $x4 = $dx - $y2o * $k;
    $y4 = $dy - $x2o * $k;

    imageline($im, $x1, $y1, $dx, $dy, $color);
    imagefilledpolygon($im, array($x2, $y2, $x3, $y3, $x4, $y4), 3, $color);
}

function dashedarrow($im, $x1, $y1, $x2, $y2, $alength, $awidth, $color) {
    $distance = sqrt(pow($x1 - $x2, 2) + pow($y1 - $y2, 2));

    $dx = $x2 + ($x1 - $x2) * $alength / $distance;
    $dy = $y2 + ($y1 - $y2) * $alength / $distance;

    $k = $awidth / $alength;

    $x2o = $x2 - $dx;
    $y2o = $dy - $y2;

    $x3 = $y2o * $k + $dx;
    $y3 = $x2o * $k + $dy;

    $x4 = $dx - $y2o * $k;
    $y4 = $dy - $x2o * $k;

    imagedashedline($im, $x1, $y1, $dx, $dy, $color);
    imagefilledpolygon($im, array($x2, $y2, $x3, $y3, $x4, $y4), 3, $color);
}

	
	//esegue il merging di due immagini circondate da uno spazio di 25 px
function mergeImgRight($a,$b){

	$imga = $a["img"];
	$imgax = $a["x"];
	$imgay = $a["y"];
	
	$imgb = $b["img"];
	$imgbx = $b["x"];
	$imgby = $b["y"];
	
	$outx = ($imgax+$imgbx+100);
	$outy = $imgay/*$imgby*/+50;

	
	$out = ImageCreate($outx , $outy);
	$bianco = ImageColorAllocate($out,255,255,255);
	
	//copio la prima immagine nell'output
	imagecopy($out,$imga,25,25,0,0,$imgax,$imgay);
	//e poi la seconda
	imagecopy($out,$imgb,$outx-($imgbx+25),25,0,0,$imgbx,$imgby);
	
	$array["img"]=$out; $array["x"] = $outx; $array["y"] = $outy; 
	return $array;
}

function mergeImgUnder($a,$b){

	$imga = $a["img"];
	$imgax = $a["x"];
	$imgay = $a["y"];
	
	$imgb = $b["img"];
	$imgbx = $b["x"];
	$imgby = $b["y"];
	
	$outx = $imgax/*$imgby*/+50;
	$outy = ($imgay+$imgby+100);

	
	$out = ImageCreate($outx , $outy);
	$bianco = ImageColorAllocate($out,255,255,255);
	
	//copio la prima immagine nell'output
	imagecopy($out,$imga,25,25,0,0,$imgax,$imgay);
	//e poi la seconda
	imagecopy($out,$imgb,25,$outy-($imgby+25),0,0,$imgbx,$imgby);
	
	$array["img"]=$out; $array["x"] = $outx; $array["y"] = $outy; 
	return $array;
}


function connect($out,$im1,$im2,$where,$dash = false){
	$black = ImageColorAllocate($out["img"],0,0,0);
	switch($where){
		case "right": if($dash==true) {dashedarrow ($out["img"], $im1["x"]+25,(($im1["y"])/2)+25, $out["x"]-($im2["x"]+25),(($im2["y"])/2)+25,5,5, $black);}
					  else {arrow ($out["img"], $im1["x"]+25,(($im1["y"])/2)+25, $out["x"]-($im2["x"]+25),(($im2["y"])/2)+25,5,5, $black);}break;
		case "under": if($dash==true) {
						imagedashedline ($out["img"], $im1["x"]+25,(($im1["y"])/2)+25, $out["x"]-1,(($im1["y"])/2)+25, $black);
						imagedashedline ($out["img"], $out["x"]-1,(($im1["y"])/2)+25, $out["x"]-1,$im1["y"]+50, $black);
						imagedashedline ($out["img"], $out["x"],$im1["y"]+50, 0 ,$im1["y"]+50, $black);
						imagedashedline ($out["img"],  0 ,$im1["y"]+50, 0 ,$out["y"]-((($im2["y"])/2)+25), $black);
						dashedarrow ($out["img"], 0 ,$out["y"]-((($im2["y"])/2)+25), $out["x"]-($im2["x"]+25 ),$out["y"]-((($im2["y"])/2)+25),5,5, $black);
					  }
					  else {
					  	imageline ($out["img"], $im1["x"]+25,(($im1["y"])/2)+25, $out["x"]-1,(($im1["y"])/2)+25, $black);
						imageline ($out["img"], $out["x"]-1,(($im1["y"])/2)+25, $out["x"]-1,$im1["y"]+50, $black);
						imageline ($out["img"], $out["x"],$im1["y"]+50, 0 ,$im1["y"]+50, $black);
						imageline ($out["img"],  0 ,$im1["y"]+50, 0 ,$out["y"]-((($im2["y"])/2)+25), $black);
						arrow ($out["img"], 0 ,$out["y"]-((($im2["y"])/2)+25), $out["x"]-($im2["x"]+25 ),$out["y"]-((($im2["y"])/2)+25),5,5, $black);
					  }break;
	}
}


//ogni immagine Image è dentro un array image dove image["img"] è l'immagine, image["x"] e img["y"] la x e la y

$im1["img"] =  imageCreateFromPNG("TaskBoxTest.png");
$size1 = getImageSize("TaskBoxTest.png");
$im1["x"] =  $size1[0];
$im1["y"] =  $size1[1];

$im2["img"] =  imageCreateFromPNG("TaskBoxTest.png");
$size2 = getImageSize("TaskBoxTest.png");
$im2["x"] =  $size2[0];
$im2["y"] =  $size2[1];

$out = mergeImgUnder($im1,$im2);
$out2 = mergeImgRight($im1,$im2);

//connette le due immagini una sotto l'altra con linea tratteggiata
//connect($out,$im1,$im2,"under",true);
//connette le due immagini una sotto l'altra con linea unita
//connect($out,$im1,$im2,"under");
//connette le due immagini una accanto l'altra con linea tratteggiata
//connect($out2,$im1,$im2,"right",true);
//connette le due immagini una accanto l'altra con linea unita
connect($out2,$im1,$im2,"right");



Imagepng($out2["img"]);
ImageDestroy($out2["img"]);
?>
