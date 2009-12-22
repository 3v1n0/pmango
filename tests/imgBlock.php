<?php

function hex2dec($hex) {
	$color = str_replace('#', '', $hex);
	
	return array(
		'r' => hexdec(substr($color, 0, 2)),
		'g' => hexdec(substr($color, 2, 2)),
		'b' => hexdec(substr($color, 4, 2))
	);
}

abstract class ImgBlock {
	private $pFgColor;
	private $pBgColor;
	private $pMinWidth;
	private $pMinHeight;
	private $pMaxWidth;
	private $pMaxHeight;
	private $pBorder;
	private $pBorderColor;
	
	protected function ImgBlock() {
		$this->setBgColor("#FFFFFF");
		$this->setFgColor("#000000");
		$this->setBorderColor("#000000");
		$this->setBorder(1);
		$this->setMaxWidth(0);
		$this->setMaxHeight(0);
		$this->setMinWidth(0);
		$this->setMinHeight(0);
	}
	
	public function setBorder($b) {
		$this->pBorder = intval($b);
	}
	
	public function getBorder() {
		return $this->pBorder;
	}
	
	public function setMaxWidth($w) {
		$this->pMaxWidth = intval($w);
	}
	
	public function setMaxHeight($h) {
		$this->pMaxHeight = intval($h);
	}
	
	public function setMinWidth($w) {
		$this->pMinWidth = intval($w);
	}
	
	public function setMinHeight($h) {
		$this->pMinHeight = intval($h);
	}
	
	public function getMaxWidth() {
		return $this->pMaxWidth;
	}
	
	public function getMaxHeight() {
		return $this->pMaxHeight;
	}
	
	public function getMinWidth() {
		return $this->pMinWidth;
	}
	
	public function getMinHeight() {
		return $this->pMinHeight;
	}
	
	public function setFgColor($c) {
		if ($c != null)
			$this->pFgColor = hex2dec($c);
	}
	
	public function getFgColor() {
		return $this->pFgColor;
	}
	
	public function setBorderColor($c) {
		$this->pBorderColor = hex2dec($c);
	}
	
	public function getBorderColor() {
		return $this->pBorderColor;
	}
	
	public function setBgColor($c) {
		if ($c != null)
			$this->pBgColor = hex2dec($c);
	}
	
	public function getBgColor() {
		return $this->pBgColor;
	}
	
	public function getBorderedImage($padding = 0, $align = "center") {
		global $bordersize, $minsize, $maxsize, $size;

		$w = $this->getWidth();
		$h = $this->getHeight();
		$b = $this->pBorder; //intval($bordersize * $multiplier);
	
		if ($this->getMaxWidth() > 0)
			$w = $this->getMaxWidth();
			
		if ($this->getMaxHeight() > 0)
			$h = $this->getMaxHeight();//imagesy($img) ;//+ intval(imagesy($img) * 5 / 100); // * $multiplier
	
		$h += $padding*2 + $this->pBorder*2;
		$w += $padding*2 + $this->pBorder*2;
	
	/*
			 print_r($txt_size); echo $w."x$h";
	*/
	
		// XXX Check bigger image!
	
		$tbx = imagecreate($w, $h);
	//	$tbx = imagecreatetruecolor($w, $h);
	//	imageantialias($tbx, true);
	
		$bg = $this->getBgColor();
		$fg = $this->getBorderColor();
		$background_color = imagecolorallocate($tbx, $bg['r'], $bg['b'], $bg['g']);
		$border_color = imagecolorallocate($tbx, $fg['r'], $fg['b'], $fg['g']);
	
		imagefilledrectangle($tbx, 0, 0, $w-1, $h-1, $border_color);
		imagefilledrectangle($tbx, $b, $b, $w-$b-1, $h-$b-1, $background_color);
	
		if ($align == "center") {
			$imgX = intval((imagesx($tbx) - $this->getWidth()) / 2);
		} else if ($align == "right") {
			$imgX = $w - $this->getWidth() - $this->pBorder - $padding;
		} else {
			$imgX = $this->pBorder + $padding;
		}

		$imgY = intval(($h - $this->getHeight()) / 2); //valign = middle
	
		imagecopy($tbx, $this->getImage(), $imgX, $imgY, 0, 0, $this->getWidth(), $this->getHeight());
	
		return $tbx;
	}
	
	public abstract function getHeight();
	public abstract function getWidth();
	
	//buildImage(), getImage(), destroy() / chached?
	public abstract function getImage();
}

class TextBlock extends ImgBlock {
	private $pText;
	private $pFont;
	private $pFontSize;
	private $pAlign;
	
	private $pTextBox;
	private $pTextLines;
	private $pTextLinesInfo;
	private $pTextWidth;
	private $pTextHeight;
	
	public function TextBlock($text, $font, $font_size, $align = "center") {
		parent::ImgBlock();
		$this->pText = $text;
		$this->pAlign = $align;
		$this->pFont = $font;
		$this->pFontSize = $font_size;
		$this->pTextBox = $this->getTextSize();
		$this->processText();
	}
	
	public function setText($text) {
		$this->pText = $text;
		$this->pTextBox = $this->getTextSize();
		$this->processText();
	}
	
	public function setAlign($align) {
		$this->pAlign = $align;
	}
	
	public function setMaxWidth($w) {
		parent::setMaxWidth($w);
		
		if (isset($this->pText))
			$this->processText();
	}
	
	public function setMinHeight($h) {
		parent::setMinHeight($h);
		
		if (isset($this->pText))
			$this->processText();
	}
	
	public function setMaxHeight($h) {
		parent::setMaxHeight($h);
		
		if (isset($this->pText))
			$this->processText();
	}
	
	public function getWidth() {
		return $this->pTextWidth;
	}
	
	public function getHeight() {
		return $this->pTextHeight;
	}
	
	public function getTextBox() {
		return $this->pTextBox;
	}
	
	private function processText() {
		$vspace = intval($this->pFontSize * 20 / 100);
		$hspace = 0; // FIXME, bugged if > 0
		
		$this->pTextLines = array();
		$this->pTextLinesInfo = array();
		$this->pTextWidth = 0;
		$this->pTextHeight = 0;
	
		foreach (explode("\n", $this->pText) as $line) {
			$stripped_line = strip_tags($line);
			$lsize = $this->getTextSize($stripped_line);
	
			if ($this->getMaxWidth() > 0 && $lsize['w'] > $this->getMaxWidth()) {
				$line = $this->getFixedText($line);
				$stripped_line = strip_tags($line);
				$lsize = $this->getTextSize($stripped_line);
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
						$tsize = $this->getTextSize(strip_tags(substr($line, 0, $start)));
						$underline['start'] = $tsize['w'];
						
						// XXX code below tries to fix the intermediate underline position
//						$pre_size = getTextSize(strip_tags(substr($line, 0, $start)));
//						$part_size = getTextSize(substr($line, $start+3, $end-3-$start));
//						$full_size = getTextSize(strip_tags(substr($line, 0, $end)));
//						
//						$underline['start'] += $full_size['w'] - ($pre_size['w'] + $part_size['w']);
	
//						//echo "checking part [$start - $end]:".substr($line, $start+3, $end-3-$start)."\n";
//						//print_r($part_size);
//						//echo " vs full: [0 - $end]".strip_tags(substr($line, 0, $end))." | ".substr($line, 0, $end)."\n";
//						//print_r($full_size);
//						//echo " vs pre [0 - $start]:".strip_tags(substr($line, 0, $start))." | ".substr($line, 0, $start)."\n";
//						//print_r($pre_size);
//						//echo "\nStandard pos: ${underline['start']}";
					}
	
					$tsize = $this->getTextSize(substr($line, $start+3, $end-3-$start));
					$underline['end'] = $underline['start']+$tsize['w']-3;
	
					$lsize['u'][] = $underline;
					$pre_end = $end;
				}
			} while ($start !== false);
	
			$this->pTextWidth = max($this->pTextWidth, $lsize['w']);
			$this->pTextHeight += $lsize['h'] + $vspace;
			$lsize['top'] = $this->pTextHeight - $lsize['h'];
			
			if ($this->pTextHeight > $this->getMaxHeight() && $this->getMaxHeight() > 0) {
				$this->pTextHeight -= $lsize['h'] + $vspace;
				break;
			}
	
			$this->pTextLines[] = $line;
			$this->pTextLinesInfo[] = $lsize;
		}
	
		$this->pTextHeight += $hspace*2;
	}
	
	public function getImage() {
					
		$txtimg = imagecreatetruecolor($this->pTextWidth, $this->pTextHeight);
		
		$bg = $this->getBgColor();
		$background_color = imagecolorallocate($txtimg, $bg['r'], $bg['b'], $bg['g']);
		imagefilledrectangle($txtimg, 0, 0, $this->pTextWidth, $this->pTextHeight, $background_color);
	
		for ($i = 0; $i < count($this->pTextLines); $i++) {
			$lsize = $this->pTextLinesInfo[$i];
			$text = strip_tags($this->pTextLines[$i]);
	
			$timg = imagecreatetruecolor($this->pTextWidth, $lsize['h']+1);
			//imageantialias($timg, true);
			imagefilledrectangle($timg, 0, 0, $this->pTextWidth, $lsize['h'], $background_color);
	
			if ($this->pAlign == "center") {
				$padding = intval(($this->pTextWidth - $lsize['w']) / 2);
			} else if ($this->pAlign == "right") {
				$padding = $this->pTextWidth - $lsize['w'] - $hspace/2 + 1;
			} else /*($this->pAlign == "left") */ {
				$padding = $hspace/2 - 1;
			}
	
			$fg = $this->getFgColor();
			$font_color = imagecolorallocate($timg, $fg['r'], $fg['b'], $fg['g']);
	
			$txtX = $padding;
			$txtY = $this->pFontSize ; //+ $lsize['box'][0] - $lsize['box'][1]
								//intval($font_size + (imagesy($timg) - $lsize['h'])/2);
	
			if ($lsize['h'] <= $this->pFontSize)
					$txtY -= ($this->pFontSize - $lsize['h'] - $lsize['box'][0]) + 1;
	//		else //XXX moves up the "gggph" text
	//				$txtY += $lsize['box'][1];
	
			// FIXME: afaasj at 14px
			imagettftext($timg, $this->pFontSize, 0, $txtX, $txtY, $font_color, $this->pFont, $text);
	
			if (isset($lsize['u'])) {
				// FIXME: text like "ggguuuu"
				// imageline($timg, $padding, $lineY, $padding + $lsize['w'] /*- $hspace*/, $lineY, $font_color);
	
				$lineY = $lsize['h']-$lsize['box'][3]-1;
				foreach ($lsize['u'] as $underlined)
					imageline($timg, $padding + $underlined['start'], $lineY, $padding + $underlined['end'] /*- $hspace*/, $lineY, $font_color);
			}
	
			imagecopy($txtimg, $timg, 0, $lsize['top'], 0, 0, imagesx($timg), imagesy($timg));
			imagedestroy($timg);
		}
	
		return $txtimg;
	}
	
	/* Private stuff */
	
	private function getTextSize($text = null) {
		
		if ($text == null)
			$text = $this->pText;
	
		$txtbox = imagettfbbox($this->pFontSize, 0, $this->pFont, $text);
		$txtW = abs(max($txtbox[2], $txtbox[4])) + abs(max($txtbox[0], $txtbox[6]));
		$txtH = abs(max($txtbox[5], $txtbox[7])) + abs(max($txtbox[1], $txtbox[3]));
	
	//echo "$text = $txtW x $txtH\n";print_r($txtbox);echo "\n";
	
		return array('w' => $txtW, 'h' => $txtH, 'box' => $txtbox);
	}
	
//	public function setWrappingFunc() {}
	
	private function getFixedText($text /*$trim_func*/) {

		$tsize = $this->getTextSize(strip_tags($text));
	
		if ($tsize['w'] < $this->getMaxWidth())
			return $text;
	
		while ($tsize['w'] >= $this->getMaxWidth() && strlen($text) > 1) {
			$cut = -1;
	
			if ($tsize['w'] > $this->getMaxWidth()*2 + 2)
				$cut = strlen($text)/2;
	
			$text = substr($text, 0, $cut);
			$tsize = $this->getTextSize(strip_tags($text)."...");
		}
	
		return $text."...";
	}
}

class ColorBlock extends ImgBlock {
	
	public function ColorBlock($fgcolor = null) {
		parent::ImgBlock();
		$this->setFgColor($fgcolor);
	}
	
//	public function ColorBlock($color) {
//		$this->ColorBlock($color, null);
//	}
	
	public function getWidth() {
		return $this->getMaxWidth();
	}
	
	public function getHeight() {
		return $this->getMaxHeight();
	}
	
	public function getImage() {
		$img = imagecreate($this->getWidth(), $this->getHeight());
		$color = $this->getFgColor();
		
		$bg = imagecolorallocate($img, $color['r'], $color['g'], $color['b']);
		imagefilledrectangle($img, 0, 0, $this->getWidth()-1, $this->getHeight()-1, $bg);
		
		return $img;
	}
}

class HorizontalBoxBlock extends ImgBlock {
	private $pBlocks;
	private $pSpace;
//	private $pBoxWidth;
//	private $pBoxHeight;
	
	public function HorizontalBoxBlock($border = 1, $space = 0) {
		parent::ImgBlock();
		$this->setBorder($border);
		$this->pBlocks = array();
		$this->pSpace = 0;
	}
	
	public function addBlock($block) {
//		if (!is_subclass_of($bloc, "ImgBlock"))
//			return;
//echo "adding file\n";
		//$block->setBorder($this->getBorder());
		$this->pBlocks[] = $block;
		/// XXX compute the size while adding.
	}
	
	public function setSpace($s) {
		$this->pSpace = $s;
	}
	
	public function getWidth() {
		$w = 0;
		
		foreach ($this->pBlocks as $block) {
			$w += $block->getWidth() + $this->getBorder() + $this->pSpace*2;}
			
		return $w - $this->getBorder() - 1;
	}
	
	public function getHeight() {
		$h = 0;
		
		foreach ($this->pBlocks as $block)
			$h = max($h, $block->getHeight());
		
		$h += $this->pSpace*2 - 1;
		
		return $h;
	}
	
	public function getImage() {

		$w = $this->getWidth();
		$h = $this->getHeight();
		
		if ($this->getMaxWidth() > 0)
			$w = $this->getMaxWidth() - $this->getBorder() - $this->pSpace;
		
		if ($this->getMaxHeight() > 0)
			$h = $this->getMaxHeight() - $this->getBorder() - $this->pSpace;
			
		$box = imagecreate($w, $h);
	
		$bgcolor = $this->getBgColor();
		$bg = imagecolorallocate($box, $bgcolor['r'], $bgcolor['g'], $bgcolor['b']);
		
		imagefilledrectangle($box, 0, 0, $w-1, $h-1, $bg);
		
		$xPos = -$this->getBorder()-1;
		$yPos = -$this->getBorder()-1;
		
		foreach ($this->pBlocks as $block) {
			$old_bd = $block->getBorder();
			$old_mw = $block->getMaxWidth();
			$old_mh = $block->getMaxHeight();
			
			//XXX apply also the block 
			$block->setBorder($this->getBorder());
//			if ($this->getMaxWidth() > 0)
//			$block->setMaxWidth(10);
			//$block->setMaxWidth($w/count($this->pBlocks)); //$w-$this->getBorder()-$this->pSpace
			$block->setMaxHeight($h-$this->getBorder()-$this->pSpace);
//return $block->getBorderedImage();
			
			$content = $block->getBorderedImage($this->pSpace);
//			echo imagesx($content)."\n";
			imagecopy($box, $content, $xPos, $yPos, 0, 0, imagesx($content), imagesy($content));
			$xPos += imagesx($content)-$this->getBorder();
			
			$block->setBorder($old_bd);
			$block->setMaxWidth($old_mw);
			$block->setMaxHeight($old_mh);
		}
//		imagecopy($box, $main, 0, 0, 0, 0, imagesx($main), imagesy($main));
//		imagecopy($box, $child, imagesx($main)-$bordersize, 0, 0, 0, imagesx($child), imagesy($child));
		
		//destroy child?
		
		return $box;
	}
}


////////////////////////////////////////////////////////

//$blk = new ColorBlock("#AABBDD");
//$blk->setMaxHeigth(25);
//$blk->setMaxWidth(100);
//$blk->setFgColor("#CCDDEE");
//$blk->setBorder(10);

putenv('GDFONTPATH=' . realpath('../fonts/Droid'));
$font_bold = "DroidSans-Bold.ttf";
$font_normal = "DroidSans.ttf";
$font_size = 15;

$blk = new TextBlock("Testttttttttttttt\nfff\nassda\n<u>ssssssss</u>\nff<u>a</u>jslkafd<u>deee</u>", $font_normal, $font_size);
$blk->setMaxWidth(55);
$blk->setBorder(5);
//$blk->setMaxWidth($blk->getWidth()/2);
//$blk->setMaxWidth($blk->getWidth()*2);

$blk = new HorizontalBoxBlock();
$blk->setSpace(15);
//$blk->setMaxWidth(150);
$blk->setBorder(5);

$a = new TextBlock("12 d", $font_normal, $font_size);
//$a->setMaxWidth(10);
//echo $a->getWidth()."\n";
$b = new TextBlock("40 ph", $font_normal, $font_size);
$c = new TextBlock("1350 €", $font_normal, $font_size);
$d = new TextBlock("uhuh", $font_normal, $font_size);
$f = new TextBlock("mmmmmm", $font_normal, $font_size);

$blk->addBlock($a);
$blk->addBlock($b);
$blk->addBlock($c);
//$blk->addBlock($d);
//$blk->addBlock($f);
$blk->setBorderColor("#BBBBBB");

header("Content-type: image/png");
imagepng($blk->getBorderedImage());
//imagepng($blk->getImage());







class ImgBox {
	private $pBorder;
	private $pFont;
	private $pFontSize;
	private $pPadding;
	private $pMinSize;
	private $pMaxSize;
	private $img;

	public function ImgBox($border_size, $font, $font_size, $padding) { // $multiplier, $colors... 
		$pBorder = $border_size;
		$pFont = $font;
		$pFontSize = $font_size;
		$pPadding = $padding;
	}
	
	public function addLine() {}
	public function addLine1() {}

}


?>