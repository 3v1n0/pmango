<?
Header("Content-Type: image/jpeg");

//------Funzioni di disegno------------
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

function drawSpM(){
	$img = ImageCreate(50,50);
	$bianco = ImageColorAllocate($img,255,255,255);
	$nero = ImageColorAllocate($img,0,0,0);
	
	$spm["x"] =  50;
	$spm["y"] =  50;
	imageFilledEllipse($img,25,25,49,49,$nero);
	$spm["img"] = $img;
	return $spm;	
}

function drawEpM(){
	$img = ImageCreate(50,50);
	$bianco = ImageColorAllocate($img,255,255,255);
	$nero = ImageColorAllocate($img,0,0,0);
	
	$epm["x"] =  50;
	$epm["y"] =  50;
	imageFilledEllipse($img,25,25,40,40,$nero);
	imageEllipse($img,25,25,48,48,$nero);
	imageEllipse($img,25,25,49,49,$nero);
	$epm["img"] = $img;
	return $epm;	
}
//------Funzioni di disegno------------fine	

//------Funzioni di merging------------
// NOTA BENE di seguito con TN è indicata erroneamente una riga delle TN, le cambierò in seguito 
//esegue il merging di due immagini separete da 50 px di spazio
//da modificare in modo che unisca un array di immagini variabile
function mergeImgRight($TN,$b){

	$imgTN = $TN["img"];
	$imgTNx = $TN["x"];
	$imgTNy = $TN["y"];
	
	$imgb = $b["img"];
	$imgbx = $b["x"];
	$imgby = $b["y"];
	
	//alloco i punti mediani dei lati della tbx dentro b
	$b["leftx"] = $imgTNx+250; //gap tra tbx + la Spm
	$b["lefty"] = ($imgby/2)+25;  
	$b["rightx"] = $imgTNx+250+$imgbx;
	$b["righty"] = $imgby/2+25;
	
	$outx = ($imgTNx+$imgbx+100);
	$outy = max($imgTNy, $imgby);

	
	$out = ImageCreate($outx , $outy);
	$bianco = ImageColorAllocate($out,255,255,255);
	
	//copio la prima immagine nell'output
	imagecopy($out,$imgTN,0,0,0,0,$imgTNx,$imgTNy);
	//e poi la seconda
	imagecopy($out,$imgb,$outx-($imgbx+50),0,0,0,$imgbx,$imgby);
	
	$index = $TN["index"]; $i = $TN["i"];$h = $TN["h"];
	$index[$h][$i]= $b;
	$i++; 
	
	
	$TN["img"]=$out; $TN["x"] = $outx; $TN["y"] = $outy; $TN["index"] = $index; $TN["i"] = $i;
	return $TN;
}

//merging di due TN separate da 50 px
function mergeTNUnder($TN,$TN2){

	$imgTN = $TN["img"];
	$imgTNx = $TN["x"];
	$imgTNy = $TN["y"];
	$indexTN = $TN["index"];
	
	$imgTN2 = $TN2["img"];
	$imgTN2x = $TN2["x"];
	$imgTN2y = $TN2["y"];
	$indexTN2 = $TN2["index"][0];
	
			
	$outx = max($imgTNx,$imgTN2x);
	$outy = ($imgTNy+$imgTN2y);

	
	$out = ImageCreate($outx , $outy);
	$bianco = ImageColorAllocate($out,255,255,255);
	
	//copio la prima immagine nell'output centrata
	imagecopy($out,$imgTN,($outx/2)-($imgTNx/2),0,0,0,$imgTNx,$imgTNy);
	//e poi la seconda centrata
	imagecopy($out,$imgTN2,($outx/2)-($imgTN2x/2),$outy-$imgTN2y,0,0,$imgTN2x,$imgTN2y);
	
	//cambio le coordinate della y e della x delle tbx inserite
	if(($imgTNx-$imgTN2x)>=0){
		for($cont=0;$cont<sizeof($indexTN2);$cont++){
			$indexTN2[$cont]["leftx"] += (($imgTNx/2)-($imgTN2x/2)); //la quantità che lo porta centrato
			$indexTN2[$cont]["lefty"] += $outy-$imgTN2y; 
			$indexTN2[$cont]["rightx"] +=(($imgTNx/2)-($imgTN2x/2)); //la quantità che lo porta centrato 
			$indexTN2[$cont]["righty"] += $outy-$imgTN2y;
		}
	}
	else { 
		
		$indexTN = incrementmatrix($indexTN,(($imgTN2x/2)-($imgTNx/2)));
		for($cont=0;$cont<sizeof($indexTN2);$cont++){
			$indexTN2[$cont]["lefty"] += $outy-$imgTN2y; 
			$indexTN2[$cont]["righty"] += $outy-$imgTN2y;
		}
		
		
	}
	
	
	
	$h = $TN["h"];
	$indexTN[$h]= $indexTN2;
	$h++; 
	
	$TN["img"]=$out; $TN["x"] = $outx; $TN["y"] = $outy; $TN["index"] =$indexTN; $TN["h"]= $h;
	return $TN;
}

function mergeArrayRight($TaskNetwork, $array){

	foreach($array as $tbx){
		$TaskNetwork = mergeImgRight($TaskNetwork,$tbx);
	}
	
		//ingrandisco il disegno di 25 px in alto e in basso
		$out = ImageCreate($TaskNetwork["x"] , $TaskNetwork["y"]+50);
		$bianco = ImageColorAllocate($out,255,255,255);
		
		imagecopy($out,$TaskNetwork["img"],0,25,0,0,$TaskNetwork["x"],$TaskNetwork["y"]);
		
		$TaskNetwork["img"]=$out; $TaskNetwork["y"] = $TaskNetwork["y"]+50;
		
				
	return $TaskNetwork;
	
	
}

function mergeArrayUnder($TaskNetwork, $array){

	foreach($array as $TNrow){

		$TaskNetwork = mergeTNUnder($TaskNetwork,$TNrow);

	}
	
	
	//ora rendo assolute le coordinate relative delle tbx inserite
	//	$TaskNetwork["index"] = transformCoordinateRow($TaskNetwork);

	return $TaskNetwork;
}
//------Funzioni di merging------------fine
function mergeSpM($TaskNetwork, $spm){
	$imgTN = $TaskNetwork["img"];
	$imgTNx = $TaskNetwork["x"];
	$imgTNy = $TaskNetwork["y"];
	
	$imgspm = $spm["img"];
	$imgspmx = $spm["x"];
	$imgspmy = $spm["y"];
	
	
	$outx = ($imgTNx+$imgspmx+(200-$imgspmx)); //$imgTNx +200
	$outy = max($imgTNy, $imgspmy);

	//alloco i punti mediani del lato destro della SpM
	$spm["rightx"] = $imgspmx;
	$spm["righty"] = $outy/2;
	
	$out = ImageCreate($outx , $outy);
	$bianco = ImageColorAllocate($out,255,255,255);

	//copio la prima SpM nell'output
	imagecopy($out,$imgspm,0,($outy/2)-($imgspmy/2),0,0,$imgspmx,$imgspmy);
	//e poi la TN
	imagecopy($out,$imgTN,($outx-$imgTNx),0,0,0,$imgTNx,$imgTNy);
	
	$index = $TaskNetwork["index"]; $i = $TaskNetwork["i"];$h = $TaskNetwork["h"];
	$index[$h][$i]= $spm;
	$i++; 
	
	
	$TaskNetwork["img"]=$out; $TaskNetwork["x"] = $outx; $TaskNetwork["y"] = $outy; $TaskNetwork["index"] = $index; $TaskNetwork["i"] = $i;
	return $TaskNetwork;
	
}

function mergeEpM($TaskNetwork, $epm){
	$imgTN = $TaskNetwork["img"];
	$imgTNx = $TaskNetwork["x"];
	$imgTNy = $TaskNetwork["y"];
	
	$imgepm = $epm["img"];
	$imgepmx = $epm["x"];
	$imgepmy = $epm["y"];
	
	$outx = ($imgTNx+$imgepmx+(200-$imgepmx));
	$outy = max($imgTNy, $imgepmy);

	//alloco i punti mediani del lato destro della SpM
	  
	$epm["leftx"] = $outx-$imgepmx;
	$epm["lefty"] = $outy/2;
	
	$out = ImageCreate($outx , $outy);
	$bianco = ImageColorAllocate($out,255,255,255);

	//copio la prima SpM nell'output
	imagecopy($out,$imgTN,0,0,0,0,$imgTNx,$imgTNy);
	//e poi la TN
	imagecopy($out,$imgepm,($outx-$imgepmx),($outy/2)-($imgepmy/2),0,0,$imgepmx,$imgepmy);
	
	$index = $TaskNetwork["index"]; $i = $TaskNetwork["i"];$h = $TaskNetwork["h"];
	$index[$h][$i]= $epm;
	$i++; 
	
	
	$TaskNetwork["img"]=$out; $TaskNetwork["x"] = $outx; $TaskNetwork["y"] = $outy; $TaskNetwork["index"] = $index; $TaskNetwork["i"] = $i;
	return $TaskNetwork;
	
}

//------Funzioni di setting------------
function incrementmatrix($matrix,$inc){
	for($i=0;$i<sizeof($matrix);$i++){
		for($j=0;$j<sizeof($matrix[$i]);$j++){
			$matrix[$i][$j]["leftx"] += $inc;
			$matrix[$i][$j]["rightx"] += $inc;
		}
	}
	return $matrix;
}

function connect($TaskNetwork, $ID1,$ID2){
	//ID 1 e 2 sono due array ID["riga"] ID["colonna"]
	$im = $TaskNetwork;
	$index = $TaskNetwork["index"];
	$tbx1 = $index[$ID1["riga"]][$ID1["colonna"]];
	$tbx2 = $index[$ID2["riga"]][$ID2["colonna"]];
	
	drawline($im,$tbx1,$tbx2,"right");
	
}

function drawline($out,$im1,$im2,$where,$dash = false){
	$black = ImageColorAllocate($out["img"],0,0,0);
	switch($where){
		case "right": if($dash==true) {dashedarrow ($out["img"], $im1["rightx"],$im1["righty"], $im2["leftx"],$im2["lefty"],5,5, $black);}
					  else {arrow ($out["img"], $im1["rightx"],$im1["righty"], $im2["leftx"],$im2["lefty"],5,5, $black);}break;

		//NON FUNZIONANTE//
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


//DEPRECATED
/*
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


//DEPRECATED
/*function transformCoordinateRow($TaskNetwork){
	$x = 50; $y = 25;
	$index = $TaskNetwork["index"];
	
	for($k=0;$k<count($index); $k++){
		$tbx = $index[$k];
		$tbx["leftx"] += $x;
		$tbx["lefty"] += $y; 
		$tbx["rightx"] += $x;
		$tbx["righty"] += $y;
		$x =  $tbx["rightx"]+100;
		$index[$k] = $tbx;
	}
	
	return $index;
	
}
*/	
//------Funzioni di setting------------fine


/// creazione TN vuota
$index; $i=0;$h=0;
$TaskNetwork["img"] = ImageCreate(1,1); //immagine vuota iniziale della TN
$TaskNetwork["x"] = 1; 					//larghezza della TN
$TaskNetwork["y"] = 1;					//altezza della TN
$TaskNetwork["index"] = $index;			//matrice delle TBx presenti nella TN
$TaskNetwork["i"] = $i;					//indice delle colonne precedenti
$TaskNetwork["h"] = $h;					//indice delle righe precedenti

//creazione startprojectmilestone
$spm = drawSpM();

//creazione endprojectmilestone
$epm = drawEpM();


//ogni immagine Image è dentro un array im dove im["img"] è l'immagine, im["x"] e im["y"] la x e la y
$array;

for($r=0;$r<4;$r++){
	
$l = rand ( 1 , 5 );

	for ($j=0;$j<$l;$j++){
		$im["img"] =  imageCreateFromPNG("TaskBoxTest.png");
		$size = getImageSize("TaskBoxTest.png");
		$im["x"] =  $size[0];
		$im["y"] =  $size[1];
		$array[$r][$j] = $im;
	}
$out[$r] = mergeArrayRight($TaskNetwork, $array[$r]);
	
}


$final = mergeArrayUnder($TaskNetwork,$out);
$finalSpm = mergeSpM($final, $spm);
$finalpm = mergeEpM($finalSpm, $epm);

/*
echo "<pre>";
print_r($finalpm);
echo "</pre>";
*/

//$ID1["riga"] = $finalpm["h"]; $ID1["colonna"] = 0; //Spm
$ID2["riga"] = $finalpm["h"]; $ID2["colonna"] = 1; //Epm
for($a=0;$a<$finalpm["h"];$a++){
		for($b=0;$b<sizeof($finalpm["index"][$a]);$b++){
			$ID1["riga"] = $a; $ID1["colonna"] = $b;
			connect($finalpm,$ID1,$ID2);
			
		}
}



/* DEPRECATED
$out = mergeImgUnder($im1,$im2);
$out2 = mergeImgRight($im1,$im2);

//connette le due immagini una sotto l'altra con linea tratteggiata
//drawline($out,$im1,$im2,"under",true);
//connette le due immagini una sotto l'altra con linea unita
//drawline($out,$im1,$im2,"under");
//connette le due immagini una accanto l'altra con linea tratteggiata
//drawline($out2,$im1,$im2,"right",true);
//connette le due immagini una accanto l'altra con linea unita
//drawline($out2,$im1,$im2,"right");

*/

Imagepng($finalpm["img"]);
ImageDestroy($finalpm["img"]);
?>
