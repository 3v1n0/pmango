<?
require "../TaskBox.class.php";

Header("Content-Type: image/png");


//------Funzioni di disegno------------
function imageboldline($image, $x1, $y1, $x2, $y2, $color, $thick = 3)
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

function boldarrow($im, $x1, $y1, $x2, $y2, $alength, $awidth, $color) {
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

    imageboldline($im, $x1, $y1, $dx, $dy, $color);
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

function connect($TaskNetwork,$mapBlank, $ID1,$ID2,$criticalPath=false, $dash = false,$under= false,$upper=false, $dist=0){
	$line;$arrow;
	if(!$criticalPath){	
		if($dash){
			$line="imagedashedline";
			$arrow="dashedarrow";
		}
		else{
			$line="imageline";
			$arrow="arrow";
		}
	}else{
		$line="imageboldline";
		$arrow="boldarrow";
	}
	
	
	//ID 1 e 2 sono due array ID["riga"] ID["colonna"]
	$img = $TaskNetwork["img"];
	$black = ImageColorAllocate($img,0,0,0);
	$index = $TaskNetwork["index"];
	$tbx1 = $index[$ID1["riga"]][$ID1["colonna"]];
	$tbx2 = $index[$ID2["riga"]][$ID2["colonna"]];
	

	//interi					ordinate												ascisse
	$tbx1rx = $tbx1["rightx"]-$tbx1["halfalert"]; $tbx1BlankUp = $mapBlank["y"][$ID1["riga"]]+$dist; $tbx1BlankLeft = $mapBlank[$ID1["riga"]][$ID1["colonna"]]+$dist;
	$tbx1ry = $tbx1["righty"]; $tbx1BlankDown = $mapBlank["y"][$ID1["riga"]+1]-$dist; $tbx1BlankRight =($ID1["colonna"]<sizeof($mapBlank[$ID1["riga"]])-3/*comprese 2 posizioni di inizio e fine*/) ? $mapBlank[$ID1["riga"]][$ID1["colonna"]+1]-$dist : $tbx1["rightx"]+50-$dist;
	$tbx1BlankFirst = $mapBlank[$ID1["riga"]]["inizio"]+$dist;$tbx1BlankLast = $mapBlank[$ID1["riga"]]["fine"]-$dist; 
	
	$tbx2lx = $tbx2["leftx"]; $tbx2BlankUp = $mapBlank["y"][$ID2["riga"]]+$dist; $tbx2BlankLeft = $mapBlank[$ID2["riga"]][$ID2["colonna"]]+$dist;
	$tbx2ly = $tbx2["lefty"]; $tbx2BlankDown = $mapBlank["y"][$ID2["riga"]+1]-$dist; $tbx2BlankRight =($ID2["colonna"]<sizeof($mapBlank[$ID2["riga"]])-3) ? $mapBlank[$ID2["riga"]][$ID2["colonna"]+1]-$dist : $tbx2["rightx"]+50-$dist;
	$tbx2BlankFirst = $mapBlank[$ID2["riga"]]["inizio"]+$dist;$tbx2BlankLast = $mapBlank[$ID2["riga"]]["fine"]-$dist; 
	//nel caso in cui siano Smp o Epm
	if(!isset($mapBlank["y"][$ID1["riga"]+1])){
		$tbx1BlankUp= $tbx1["righty"];
		$tbx1BlankDown= $tbx1["righty"];
		$tbx1BlankRight= $tbx1["rightx"]+50;
		$tbx1BlankFirst=$tbx1["rightx"]+50;
		$tbx1BlankLast=$tbx1["rightx"]+50;
	}
	
	if(!isset($mapBlank["y"][$ID2["riga"]+1])){
		$tbx2BlankUp= $tbx2["lefty"];
		$tbx2BlankDown= $tbx2["lefty"];
		$tbx2BlankLeft= $tbx2["leftx"]-50;
		$tbx2BlankFirst=$tbx2["leftx"]-50;
		$tbx2BlankLast=$tbx2["leftx"]-50;
	}
	/////////////////////////////////
	if($tbx1ry<$tbx2ly){//se tbx1 è piu in alto di tbx2
			if($tbx1rx>$TaskNetwork["x"]/2 or $tbx2lx>$TaskNetwork["x"]/2){//se tbx1 o tbx2 è nella metà di destra della TN
				if(!$under){
					$line($img, $tbx1rx,$tbx1ry, $tbx1BlankRight,$tbx1ry, $black);
					$line($img, $tbx1BlankRight,$tbx1ry, $tbx1BlankRight,$tbx1BlankDown, $black);
				}else{
					$line($img, $tbx1rx-($tbx1["x"]/2),$tbx1ry+($tbx1["y"]/2), $tbx1rx-($tbx1["x"]/2),$tbx1BlankDown, $black);
					$line($img, $tbx1rx-($tbx1["x"]/2),$tbx1BlankDown,$tbx1BlankRight,$tbx1BlankDown, $black);
				}
				if($tbx1BlankDown!=$tbx2BlankUp){
					if(!$under){
						$line($img,$tbx1BlankRight,$tbx1BlankDown, $tbx1BlankLast,$tbx1BlankDown, $black);
					}else{
						$line($img,$tbx1rx-($tbx1["x"]/2),$tbx1BlankDown, $tbx1BlankLast,$tbx1BlankDown, $black);
					}
					$line($img,$tbx1BlankLast,$tbx1BlankDown,$tbx1BlankLast,$tbx2BlankUp, $black);
					if(!$upper){
						$line($img,$tbx1BlankLast,$tbx2BlankUp,$tbx2BlankLeft,$tbx2BlankUp, $black);
					}else{
						$line($img,$tbx1BlankLast,$tbx2BlankUp,$tbx2lx+($tbx2["x"]/2),$tbx2BlankUp, $black);
					}
				}else{
					$line($img,$tbx1BlankRight,$tbx2BlankDown,$tbx2BlankLeft,$tbx2BlankDown, $black);						
				}
				if(!$upper){
					$line($img, $tbx2BlankLeft,$tbx2BlankUp, $tbx2BlankLeft ,$tbx2ly, $black);
					$arrow($img,  $tbx2BlankLeft ,$tbx2ly, $tbx2lx ,$tbx2ly,5,5, $black);
				}else{
					$arrow($img, $tbx2lx+($tbx2["x"]/2),$tbx2BlankUp, $tbx2lx+($tbx2["x"]/2) ,$tbx2ly-($tbx2["y"]/2)+$tbx2["halfalert"],5,5, $black);
				}
			}
			else{//tbx1 e tbx2 sono a sinistra
				if(!$under){
					$line($img, $tbx1rx,$tbx1ry, $tbx1BlankRight,$tbx1ry, $black);
					$line($img, $tbx1BlankRight,$tbx1ry, $tbx1BlankRight,$tbx1BlankDown, $black);
				}else{
					$line($img, $tbx1rx-($tbx1["x"]/2),$tbx1ry+($tbx1["y"]/2), $tbx1rx-($tbx1["x"]/2),$tbx1BlankDown, $black);
				}
				if($tbx1BlankDown!=$tbx2BlankUp){
					if(!$under){
						$line($img,$tbx1BlankRight,$tbx1BlankDown, $tbx1BlankFirst,$tbx1BlankDown, $black);
					}else{
						$line($img,$tbx1rx-($tbx1["x"]/2),$tbx1BlankDown, $tbx1BlankFirst,$tbx1BlankDown, $black);
					}
					$line($img,$tbx1BlankFirst,$tbx1BlankDown,$tbx1BlankFirst,$tbx2BlankUp, $black);
					if(!$upper){
						$line($img,$tbx1BlankFirst,$tbx2BlankUp,$tbx2BlankLeft,$tbx2BlankUp, $black);
					}else{
						$line($img,$tbx1BlankFirst,$tbx2BlankUp,$tbx2lx+($tbx2["x"]/2),$tbx2BlankUp, $black);
					}
				}else{
					$line($img,$tbx1BlankRight,$tbx1BlankDown,$tbx2BlankLeft,$tbx2BlankUp, $black);						
				}
				if(!$upper){
					$line($img, $tbx2BlankLeft,$tbx2BlankUp, $tbx2BlankLeft ,$tbx2ly, $black);
					$arrow($img,  $tbx2BlankLeft ,$tbx2ly, $tbx2lx ,$tbx2ly,5,5, $black);
				}	
				else{
					$arrow($img,  $tbx2lx+($tbx2["x"]/2) ,$tbx2BlankUp, $tbx2lx+($tbx2["x"]/2) ,$tbx2ly-($tbx2["y"]/2)+$tbx2["halfalert"],5,5, $black);
				}
			}
	}
	else{//tbx2 è piu in alto o uguale a tbx1
		if($tbx1rx>$TaskNetwork["x"]/2 or $tbx2lx>$TaskNetwork["x"]/2){//se tbx1 o tbx2 è nella metà di destra della TN
				if(!$under){
					$line($img, $tbx1rx,$tbx1ry, $tbx1BlankRight,$tbx1ry, $black);
					$line($img, $tbx1BlankRight,$tbx1ry, $tbx1BlankRight,$tbx1BlankUp, $black);
				}else{
					$line($img, $tbx1rx-($tbx1["x"]/2),$tbx1ry+($tbx1["y"]/2), $tbx1rx-($tbx1["x"]/2),$tbx1BlankDown, $black);
					$line($img, $tbx1rx-($tbx1["x"]/2),$tbx1BlankDown, $tbx1BlankRight,$tbx1BlankDown, $black);
					$line($img,$tbx1BlankRight,$tbx1BlankDown,$tbx1BlankRight,$tbx1BlankUp,$black);
				}
				if($tbx1BlankUp!=$tbx2BlankDown){
					$line($img,$tbx1BlankRight,$tbx1BlankUp, $tbx1BlankLast,$tbx1BlankUp, $black);
					$line($img,$tbx1BlankLast,$tbx1BlankUp,$tbx1BlankLast,$tbx2BlankDown, $black);
					$line($img,$tbx1BlankLast,$tbx2BlankDown,$tbx2BlankLeft,$tbx2BlankDown, $black);
				}
				else{
					$line($img,$tbx1BlankRight,$tbx2BlankDown,$tbx2BlankLeft,$tbx2BlankDown, $black);
				}
				if(!$upper){
					$line($img, $tbx2BlankLeft,$tbx2BlankDown, $tbx2BlankLeft ,$tbx2ly, $black);
					$arrow($img,  $tbx2BlankLeft ,$tbx2ly, $tbx2lx ,$tbx2ly,5,5, $black);
					
				}else{
					$line($img, $tbx2BlankLeft,$tbx2BlankDown, $tbx2BlankLeft ,$tbx2BlankUp, $black);
					$line($img,$tbx2BlankLeft ,$tbx2BlankUp, $tbx2lx+($tbx2["x"]/2),$tbx2BlankUp, $black);
					$arrow($img, $tbx2lx+($tbx2["x"]/2) ,$tbx2BlankUp, $tbx2lx+($tbx2["x"]/2) ,$tbx2ly-($tbx2["y"]/2)+$tbx2["halfalert"],5,5, $black);
				}
		}
		else{//tbx1 e tbx2 sono a sinistra
				if(!$under){
					$line($img, $tbx1rx,$tbx1ry, $tbx1BlankRight,$tbx1ry, $black);
					$line($img, $tbx1BlankRight,$tbx1ry, $tbx1BlankRight,$tbx1BlankUp, $black);
				}else{
					$line($img, $tbx1rx-($tbx1["x"]/2),$tbx1ry+($tbx1["y"]/2), $tbx1rx-($tbx1["x"]/2),$tbx1BlankDown, $black);
					$line($img,$tbx1rx-($tbx1["x"]/2),$tbx1BlankDown,$tbx1BlankRight,$tbx1BlankDown,$black);
					$line($img,$tbx1BlankRight,$tbx1BlankDown,$tbx1BlankRight,$tbx1BlankUp,$black);
				}
				if($tbx1BlankUp!=$tbx2BlankDown){
					$line($img,$tbx1BlankRight,$tbx1BlankUp, $tbx1BlankFirst,$tbx1BlankUp, $black);
					$line($img,$tbx1BlankFirst,$tbx1BlankUp,$tbx1BlankFirst,$tbx2BlankDown, $black);
					$line($img,$tbx1BlankFirst,$tbx2BlankDown,$tbx2BlankLeft,$tbx2BlankDown, $black);
				}else{
					$line($img,$tbx1BlankRight,$tbx2BlankDown,$tbx2BlankLeft,$tbx2BlankDown, $black);
				}
				if(!$upper){
					$line($img, $tbx2BlankLeft,$tbx2BlankDown, $tbx2BlankLeft ,$tbx2ly, $black);
					$arrow($img,  $tbx2BlankLeft ,$tbx2ly, $tbx2lx ,$tbx2ly,5,5, $black);
					
				}else{
					$line($img, $tbx2BlankLeft,$tbx2BlankDown, $tbx2BlankLeft ,$tbx2BlankUp, $black);
					$line($img,$tbx2BlankLeft ,$tbx2BlankUp, $tbx2lx+($tbx2["x"]/2),$tbx2BlankUp, $black);
					$arrow($img, $tbx2lx+($tbx2["x"]/2) ,$tbx2BlankUp, $tbx2lx+($tbx2["x"]/2) ,$tbx2ly-($tbx2["y"]/2)+$tbx2["halfalert"],5,5, $black);
				}
		}
	}
	
	$TaskNetwork["img"] = $img;
	return $TaskNetwork;
}

//mappa tutti i punti vuoti della TN
function mapBlank($TN){
	$index = $TN["index"];
	$map; //mappa di output, matrice
	
	//alloco il primo spazio in cima alla TN	
	$map["y"][0] = 1;
	
	//codifico nella prima riga della mappa tutte gli indici degli spazi tra righe della TN
	for($j=1;$j<sizeof($index)-1;$j++){
		$map["y"][$j] =  ($index[$j][0]["lefty"]-($index[$j][0]["y"]/2))-25;
	}
	//alloco l'ultimo spazio in fondo alla TN	
	$map["y"][(sizeof($index)-1)] =  $TN["y"] - 1;
	
	//codifico nelle restanti righe della matrice gli spazi tra tbx della stessa TN
	for($h=0;$h<sizeof($index)-1;$h++){
		$map[$h]["inizio"] = 200;
		$map[$h]["fine"] = $TN["x"] - 200;

		for($i=0;$i<sizeof($index[$h]);$i++){
			$map[$h][$i] = $index[$h][$i]["leftx"] - 50;
		}
	}
	return $map;
}


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

putenv('GDFONTPATH=' . dirname($_SERVER['SCRIPT_FILENAME']).'/../../fonts/Droid');

for($r=0;$r<4;$r++){
	
$l = rand ( 1 , 5 );

	for ($j=0;$j<$l;$j++){

		$tbx = new TaskBox(($r+1).".$j.");
		$tbx->setName("TaskBox test row $r, col $j");
		$tbx->setPlannedData("14 d", "40 ph", "1350 €");
		$tbx->setActualData("4 d", "6 ph", "230 €");
		$tbx->setPlannedTimeframe("2009.10.15", "2009.10.29");
		$tbx->setActualTimeframe("2009.10.16", "NA");
		$tbx->setResources("22 ph, Dilbert, Requirement Engineering\n".
		                   "14 ph, Wally, Sales Manager\n".
		                   "04 ph, The Boss, Manager");
		$tbx->setProgress(rand(0,100));
		$tbx->setAlerts(TaskBox::ALERT_ERROR);

		$im["x"] =  $tbx->getWidth();
		$im["y"] =  $tbx->getHeight();
		$im["img"] = $tbx->getImage();
		$im["halfalert"] = ($tbx->getAlertSize()/2);

		$array[$r][$j] = $im;
	}
$out[$r] = mergeArrayRight($TaskNetwork, $array[$r]);
	
}


$final = mergeArrayUnder($TaskNetwork,$out);
$finalSpm = mergeSpM($final, $spm);
$finalpm = mergeEpM($finalSpm, $epm);



//mappo tutti i punti vuoti della TN
$mapB = mapBlank($finalpm);

//$ID1["riga"] = $finalpm["h"]; $ID1["colonna"] = 0; //Spm
$ID2["riga"] = $finalpm["h"]; $ID2["colonna"] = 1; //Epm
$space=10;
for($a=0;$a<$finalpm["h"];$a++){
		for($b=0;$b<sizeof($finalpm["index"][$a]);$b++){
			$ID1["riga"] = $a; $ID1["colonna"] = $b;
								//img	 map			cr.path dash  under upper dist
			$finalpm = connect($finalpm,$mapB,$ID1,$ID2, false, true,false,false, $space);
		}
$space--;
}

$ID1["riga"] = $finalpm["h"]; $ID1["colonna"] = 0; //Spm
//$ID2["riga"] = $finalpm["h"]; $ID2["colonna"] = 1; //Epm
$space=10;
for($a=0;$a<$finalpm["h"];$a++){
		for($b=0;$b<sizeof($finalpm["index"][$a]);$b++){
			$ID2["riga"] = $a; $ID2["colonna"] = $b;
								//img	 map			cr.path dash  under upper dist
			$finalpm = connect($finalpm,$mapB,$ID1,$ID2, false, true,false,false);
		}
$space--;
}

$ID1["riga"] = 0; $ID1["colonna"] = 0; //Spm
$ID2["riga"] = 3; $ID2["colonna"] = 0; //Epm
$finalpm = connect($finalpm,$mapB,$ID1,$ID2, true,false,true, true,4);



Imagepng($finalpm["img"]);
ImageDestroy($finalpm["img"]);
?>
