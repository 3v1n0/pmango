<?php
//Libreria FPDF
include('lib/fpdf/fpdf.php');

//Stream handler to read from global variables
// (C) 2004, Olivier - fpdf.org
class VariableStream {
	var $varname;
	var $position;

	function stream_open($path, $mode, $options, &$opened_path) {
		$url = parse_url($path);
		$this->varname = $url['host'];
		if(!isset($GLOBALS[$this->varname])) {
			trigger_error('Global variable '.$this->varname.' does not exist', E_USER_WARNING);
			return false;
		}
		$this->position = 0;
		return true;
	}

	function stream_read($count) {
		$ret = substr($GLOBALS[$this->varname], $this->position, $count);
		$this->position += strlen($ret);
		return $ret;
	}

	function stream_eof() {
		return $this->position >= strlen($GLOBALS[$this->varname]);
	}

	function stream_tell() {
		return $this->position;
	}

	function stream_seek($offset, $whence) {
		if($whence==SEEK_SET) {
			$this->position = $offset;
			return true;
		}
		return false;
	}

	function stream_stat() {
		return array();
	}
}

class PM_FPDF extends FPDF {
	var $p_name;
	var $g_name;
	var $report_type;
	var $roles;
	var $tview;
	var $r_page;
	var $currency;
	var $w_array;

	function PM_FPDF($orientation='P', $unit='mm', $format='A4') {
		parent::FPDF($orientation, $unit, $format);

		//Register var stream protocol
		stream_wrapper_register('var', 'VariableStream');
	}

	function Header() {
		global $page;
		$this->SetFont('Arial','I',8);
		$this->SetTextColor(0,0,0);
		$this->Cell(20,3,$this->g_name,0,0,'L');
		$this->Cell(0,3,$this->p_name,0,1,'R');
		$this->Ln(3);
		if($this->report_type==2||$this->report_type==3){
			$this->SetFont('Arial','B',9);
			if($this->tview) $this->Cell($this->w_array[7],4," ",1,0,'C');
			$this->Cell($this->w_array[0],4,"%",1,0,'C');
			$this->Cell($this->w_array[1],4,"WBS",1,0, 'C');
				
			if($this->roles=="A"){
				$this->Cell($this->w_array[2]-($this->w_array[6]-$this->w_array[3]),4,"Task Name",1,0,'C');
				$this->Cell($this->w_array[6],4,"People",1,0,'C');}
				else {
					$this->Cell($this->w_array[2],4,"Task Name",1,0,'C');
			 	$this->Cell($this->w_array[3],4,"Peoples",1,0,'C');}
			 		
			 	if(!$this->tview){
			 		$this->Cell($this->w_array[4],4,"Start Date",1,0,'C');
			 		$this->Cell($this->w_array[4],4,"End Date",1,0,'C');
			 		$this->Cell($this->w_array[8],4,"Effort",1,0,'C');
			 		$this->Cell($this->w_array[5],4,"Budget",1,1,'C');}
			 		else{
			 			$this->Cell($this->w_array[4],4,"First Log",BORD,0,'C');
			 			$this->Image('modules/report/images/delta.png',$this->GetX()+1,$this->GetY()+0.9,2);
			 			$this->Cell($this->w_array[9],4,"  T",BORD,0,'C');
			 			$this->Cell($this->w_array[4],4,"Last Log",BORD,0,'C');
			 			$this->Image('modules/report/images/delta.png',$this->GetX()+1,$this->GetY()+0.9,2);
			 			$this->Cell($this->w_array[10],4,"  T",BORD,0,'C');
			 			$this->Cell($this->w_array[8],4,"Eff.",BORD,0,'C');
			 			$this->Image('modules/report/images/delta.png',$this->GetX()+1,$this->GetY()+0.9,2);
			 			$this->Cell($this->w_array[11],4,"  E",BORD,0,'C');
			 			$this->Cell($this->w_array[5],4,"Cost",BORD,0,'C');
			 			$this->Image('modules/report/images/delta.png',$this->GetX()+1,$this->GetY()+0.9,2);
			 			$this->Cell($this->w_array[12],4,"  C",BORD,1,'C');
			 		}
			 		$this->SetLineWidth(0.3);
			 		$this->SetY($this->GetY()-4);
			 		$this->Cell(0,4," ",1,1);
			 		$this->SetLineWidth(0.05);
		}
		if($this->report_type==4){
			$this->SetFont('Arial','B',9);
			$this->Cell($this->w_array[0],4,"%",'LRTB',0,'C');
			$this->Cell($this->w_array[1],4,"Dates",'LRTB',0,'C');
			$this->Cell($this->w_array[7],4,'WBS','LRTB',0,'C');
			$this->Cell($this->w_array[2],4,"Task Log Name",'LRTB',0,'C');
			$this->Cell($this->w_array[3],4,"Worker",'LRTB',0,'C');
			$this->Cell($this->w_array[4],4,"Role",'LRTB',0,'C');
			$this->Cell($this->w_array[6],4,"Effort",'LRTB',0,'C');
			$this->Cell($this->w_array[5],4,"Cost",'LRTB',0,'C');
			$this->MultiCell(0,4,"Notes",'LRTB','C');
			$this->SetFont('Arial','',8);
				
			$this->SetLineWidth(0.3);
			$this->SetY($this->GetY()-4);
			$this->Cell(0,4," ",1,1);
			$this->SetLineWidth(0.05);
		}
	}

	function Logo($title, $logo='')
	{
		$this->SetFont('Arial','B',16);
		//Title
		$this->Cell(0,10,$title,'LRTB',1,'C');
		//Logo
		if($logo) $this->Image($logo,11,17,8);
		//Line break
		$this->Ln(10);
	}

	function VMulticell($width,$cell_height,$row_number=1,$content,$border='1',$new_line=1,$align='C'){

		$first_border=$border;
		if(eregi("B", $border)) $first_border=str_replace("B", "", $border);

		if(eregi("L", $border)) $in_border.='L';
		if(eregi("R", $border)) $in_border.='R';
		if($border=='0') $in_border='0';

		$last_border=$border;
		if(eregi("T", $border)) $last_border=str_replace("T", "", $border);
		if($border=='1') {
			$first_border='LRT';
			$in_border='LR';
			$last_border='LRB';
		}

		if($row_number>1){
			$this->Cell($width,$cell_height,$content,$first_border,0,$align);
			$this->SetXY($this->GetX()-$width,$this->GetY()+ $cell_height);

			for ( $i = 0; $i <($row_number-2); $i++) {
				$this->Cell($width,$cell_height,'',$in_border,0,$align);
				$this->SetXY($this->GetX()-$width,$this->GetY()+ $cell_height);
			}
			$this->Cell($width,$cell_height,'',$last_border,0,$align);
			$this->SetXY($this->GetX()-$width,$this->GetY()+ $cell_height);

			if($new_line==0){
				$this->SetXY($this->GetX()+$width,$this->GetY()-($row_number*$cell_height));
			}else {$this->SetY($this->GetY()-$cell_height);
			$this->Ln();
			}
				
		}
		else if($row_number<=1) $this->Cell($width,$cell_height,$content,$border,$new_line,$align);
	}

	function WordWrap(&$text, $maxwidth) {

		$text = trim($text);
		if ($text==='')
		return 0;
		$space = $this->GetStringWidth(' ');
		$lines = explode("\n", $text);
		$text = '';
		$count = 0;

		foreach ($lines as $line)
		{
			$words = preg_split('/ +/', $line);
			$width = 0;

			foreach ($words as $word)
			{
				$wordwidth = $this->GetStringWidth($word);
				if ($width + $wordwidth <= $maxwidth)
				{
					$width += $wordwidth + $space;
					$text .= $word.' ';
				}
				else
				{
					$width = $wordwidth + $space;
					$text = rtrim($text)."\n".$word.' ';
					$count++;
				}
			}
			$text = rtrim($text)."\n";
			$count++;
		}
		$text = rtrim($text);
		return $count;
	}

	//Page footer
	function Footer()
	{
		//Position at 1.5 cm from bottom
		$this->SetY(-10);
		//Arial italic 8
		$this->SetFont('Arial','I',8);
		$this->SetTextColor(0,0,0);
		$this->Cell(20,3,date("d/m/Y"),0,0,'L');
		//Page number
		$this->Cell(0,3,'Page '.$this->PageNo().'/{nb}',0,0,'R');
		PM_TempY(10);
	}

	// (C) 2004 Olivier - fpdf.org
	function MemImage($data, $x=null, $y=null, $w=0, $h=0, $link='') {
		//Display the image contained in $data
		$v = 'img'.md5($data);
		$GLOBALS[$v] = $data;
		$a = getimagesize('var://'.$v);
		if(!$a)
		$this->Error('Invalid image data');
		$type = substr(strstr($a['mime'],'/'),1);
		$this->Image('var://'.$v, $x, $y, $w, $h, $type, $link);
		unset($GLOBALS[$v]);
	}

	function GDImage($im, $x=null, $y=null, $w=0, $h=0, $link='') {
		//Display the GD image associated to $im
		ob_start();
		imagepng($im);
		$data = ob_get_clean();
		$this->MemImage($data, $x, $y, $w, $h, $link);
	}
}
?>