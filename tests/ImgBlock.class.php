<?php

function hex2dec($hex) {
	$color = str_replace('#', '', $hex);

	$rgba = array ('r' => 0, 'g' => 0, 'b' => 0, 'a' => 255);

	if (strlen($color) == 3) {
		$rgba['r'] = hexdec($color[0].$color[0]);
		$rgba['g'] = hexdec($color[1].$color[1]);
		$rgba['b'] = hexdec($color[2].$color[2]);
	}

	if (strlen($color) == 6 || strlen($color) == 8) {
			$rgba['r'] = hexdec(substr($color, 0, 2));
			$rgba['g'] = hexdec(substr($color, 2, 2));
			$rgba['b'] = hexdec(substr($color, 4, 2));

			if (strlen($color) == 8)
				$rgba['a'] = hexdec(substr($color, 4, 2));
	}

	return $rgba;
}

abstract class ImgBlock {
	private $pFgColor;
	private $pBgColor;
	private $pMinWidth;
	private $pMinHeight;
	private $pMaxWidth;
	private $pMaxHeight;

	protected function ImgBlock() {
		$this->setBgColor("#FFFFFF");
		$this->setFgColor("#000000");

		$this->setMaxWidth(0);
		$this->setMaxHeight(0);
		$this->setMinWidth(0);
		$this->setMinHeight(0);
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

	public function setBgColor($c) {
		if ($c != null)
			$this->pBgColor = hex2dec($c);
	}

	public function getBgColor() {
		return $this->pBgColor;
	}

	public function setWidth($w) {
		$this->setMinWidth($w);
		$this->setMaxWidth($w);
	}

	public function setHeight($h) {
		$this->setMinHeight($h);
		$this->setMaxHeight($h);
	}

	public abstract function getHeight();
	public abstract function getWidth();

	//buildImage(), getImage(), destroy() / chached?
	public abstract function getImage();
}

class BorderedBlock extends ImgBlock {
	private $pBorder;
	private $pHPadding;
	private $pVPadding;
	private $pAlign;
	private $pContent;

	public function BorderedBlock($content, $size = 1, $hpad = 1, $vpad = 0, $align = "center") {
		parent::ImgBlock();
		$this->setContent($content);
		$this->setBorderColor($color);
		$this->setBorder($size);
		$this->setHPadding($hpad);
		$this->setVPadding($vpad);
		$this->setAlign($align);
	}

	public function setContent($content) {
		if (!is_subclass_of($content, "ImgBlock"))
			return;

		$this->pContent = $content;
	}

	public function getContent() {
		return $this->pContent;
	}

	public function setAlign($align) {
		$this->pAlign = $align;
	}

	public function setPadding($padding) {
		$this->setHPadding($padding);
		$this->setHPadding($padding);
	}

	public function setHPadding($padding) {
		$this->pHPadding = intval($padding);
	}

	public function setVPadding($padding) {
		$this->pVPadding = intval($padding);
	}

	public function setBorder($b) {
		$this->pBorder = intval($b);
	}

	public function getBorder() {
		return $this->pBorder;
	}

	public function setBorderColor($c) {
		$this->setFgColor($c);
	}

	public function getBorderColor() {
		return $this->getFgColor();
	}

	public function getWidth() {
		return $this->pContent->getWidth() + $this->pHPadding*2 + $this->pBorder*2;
	}

	public function getHeight() {
		return $this->pContent->getHeight() + $this->pVPadding*2 + $this->pBorder*2;
	}

	public function setMaxWidth($w) {
		parent::setMaxWidth($w);

		if (isset($this->pContent)) {
			$contentW = intval($w) - $this->pHPadding*2 - $this->pBorder*2;
			if ($contentW < 0) $contentW = 1;

			$this->pContent->setMaxWidth($contentW);
		}
	}

	public function setMinWidth($w) {
		parent::setMinWidth($w);

		if (isset($this->pContent)) {
			$contentW = intval($w) - $this->pHPadding*2 - $this->pBorder*2;
			if ($contentW < 0) $contentW = 1;

			$this->pContent->setMinWidth($contentW);
		}
	}

	public function setMaxHeight($h) {
		parent::setMaxHeight($h);

		if (isset($this->pContent)) {
			$contentH = $h - $this->pVPadding*2 - $this->pBorder*2;
			if ($contentH < 0) $contentH = 1;

			$this->pContent->setMaxHeight($contentH);
		}
	}

	public function setMinHeight($h) {
		parent::setMinHeight($h);

		if (isset($this->pContent)) {
			$contentH = $h - $this->pVPadding*2 - $this->pBorder*2;
			if ($contentH < 0) $contentH = 1;

			$this->pContent->setMinHeight($contentH);
		}
	}

	public function getImage() {

		$b = $this->pBorder; //intval($bordersize * $multiplier);
		$w = $this->getWidth();
		$h = $this->getHeight();

//		if ($this->getMinHeight() > 0 && $this->getHeight() < $this->getMinHeight())
//			$h = $this->getMinHeight(); // Border doesn't work

		// XXX Check bigger image!

		$blk = imagecreate($w, $h);
	//	$blk = imagecreatetruecolor($w, $h);
	//	imageantialias($blk, true);

		$bg = $this->getBgColor();
		$fg = $this->getBorderColor();
		$background_color = imagecolorallocate($blk, $bg['r'], $bg['g'], $bg['b']);
		$border_color = imagecolorallocate($blk, $fg['r'], $fg['g'], $fg['b']);

		imagefilledrectangle($blk, 0, 0, $w-1, $h-1, $border_color);
		imagefilledrectangle($blk, $b, $b, $w-$b-1, $h-$b-1, $background_color);

		if ($this->pAlign == "center") {
			$imgX = intval(($w - $this->pContent->getWidth()) / 2);
		} else if ($this->pAlign == "right") {
			$imgX = $w - $this->pContent->getWidth() - $this->pContent->pBorder - $this->pHPadding;
		} else {
			$imgX = $this->pContent->pBorder + $this->pHPadding;
		}

		$imgY = intval(($h - $this->pContent->getHeight()) / 2); //valign = middle

		imagecopy($blk, $this->pContent->getImage(), $imgX, $imgY, 0, 0,
		          $this->pContent->getWidth(), $this->pContent->getHeight());

		return $blk;
	}
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

		if (isset($this->pText) && $this->getWidth() > $this->getMaxWidth())
			$this->processText();
	}

	public function setMinWidth($w) {
		parent::setMinWidth($w);

		if (!isset($this->pText))
			return;

		if ($this->pTextWidth < $this->getMinWidth())
			$this->pTextWidth = $this->getMinWidth();
	}

	public function setMaxHeight($h) { //TODO finish!
		parent::setMaxHeight($h);

		if (isset($this->pText))
			$this->processText();
	}

	public function setMinHeight($h) {
		parent::setMinHeight($h);

		if (!isset($this->pText))
			return;

		if ($this->pTextHeight < $this->getMinHeight())
			$this->pTextHeight = $this->getMinHeight();
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

				if ($this->pTextHeight == 0) {
					$this->pTextHeight = $lsize['h'] + $vspace;
				}

				break;
			}

			$this->pTextLines[] = $line;
			$this->pTextLinesInfo[] = $lsize;
		}

		$this->pTextHeight += $hspace*2;

		if ($this->pTextHeight < $this->getMinHeight())
			$this->pTextHeight = $this->getMinHeight();

		if ($this->pTextWidth < $this->getMinWidth())
			$this->pTextWidth = $this->getMinWidth();
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

			if ($lsize['h'] <= $this->pFontSize && count($this->pTextLines) > 1) //XXX renable it and check the multline
					$txtY -= ($this->pFontSize - $lsize['h'] - $lsize['box'][0]) + 1;
	//		else //XXX moves up the "gggph" text
	//				$txtY += $lsize['box'][1];

			// Move up text like "prrrrrrr"

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

//	echo "$text = $txtW x $txtH\n";print_r($txtbox);echo "\n";

		return array('w' => $txtW, 'h' => $txtH, 'box' => $txtbox);
	}

//	public function setWrappingFunc() {}

	private function getFixedText($text /*$trim_func*/) {
		// XXX better tag handling... (short text completely underlined causes problems)

		$tsize = $this->getTextSize(strip_tags($text));

		if ($tsize['w'] < $this->getMaxWidth())
			return $text;

		$tagpos = strpos($text, "<u>");
		if ($tagpos == 0 && $tagpos !== false) {
			$text = substr($text, 3, strlen($text)-3);
			$tagHack = true;
		}

		while ($tsize['w'] >= $this->getMaxWidth() && strlen($text) > 1) {
			$cut = -1;

			if ($tsize['w'] > $this->getMaxWidth()*2 + 2)
				$cut = strlen($text)/2;

			$text = substr($text, 0, $cut);
			$tsize = $this->getTextSize(strip_tags($text)."...");
		}

		if ($tagHack == true)
			$text = "<u>".$text;

		return $text."...";
	}
}

class ColorBlock extends ImgBlock {

	public function ColorBlock($fgcolor = null) {
		parent::ImgBlock();
		$this->setFgColor($fgcolor);
		$this->setWidth(1);
		$this->setHeight(1);
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
	private $pBorders;
	private $pBordersColor; //TODO
	private $pMerge;
	private $pHomogeneous;

	public function HorizontalBoxBlock($borders = 0, $space = 0, $merge = false, $homogeneous = false) {
		parent::ImgBlock();
		$this->pBlocks = array();
		$this->pSpace = $space;
		$this->pBorders = $borders;
		$this->pMerge = $merge;
		$this->pHomogeneous = $homogeneous;
	}

	public function addBlock($block) {
		if (!is_subclass_of($block, "ImgBlock"))
			return;

		if ($this->pBorders > 0) {
			$padding = $this->pMerge ? $this->pSpace : 0;
			$block = new BorderedBlock($block, $this->pBorders, $padding);
		}

		$this->pBlocks[] = /*XXX clone  ?*/ $block;

		if ($this->getMaxWidth() > 0)
			$this->setMaxWidth($this->getMaxWidth());

		$this->setMinHeight($this->getHeight());
	}

	public function setMerge($m) {
		$this->pMerge = $m;

		if ($this->pMerge && $this->pBorders > 0) {
			foreach ($this->pBlocks as $block)
				$block->setPadding($this->pSpace);
		}
	}

	public function getMerge() {
		return $this->pMerge && $this->pBorders > 0;
	}

	public function setHomogeneous($h) {
		$this->pHomogeneous = $h;
	}

	public function getHomogeneous() {
		return $this->pHomogeneous;
	}

	public function setSpace($s) {
		if ($s > 0)
			$this->pSpace = intval($s);
	}

	public function getWidth() {
		$w = 0;
		$xtra = ($this->getMerge()) ? (-1)*$this->pBorders : $this->pSpace;

		foreach ($this->pBlocks as $block)
			$w += $block->getWidth() + $xtra;

		return $w - $xtra;
	}

	public function getContentWidth() {
		return $this->getWidth() - $this->pBorders*2;
	}

	public function getHeight() {
		$h = 0;

		foreach ($this->pBlocks as $block)
			$h = max($h, $block->getHeight());

		return $h;
	}

	public function getContentHeight() {
		return $this->getHeight() - $this->pBorders*2;
	}

	public function setMaxWidth($w) {
		parent::setMaxWidth($w);

		$count = count($this->pBlocks);

		if (!isset($this->pBlocks) || !$count || $this->getWidth() <= $this->getMaxWidth())
			 return;

		$contentW = ($w - ($this->getMerge() ? 0 : $this->pSpace)) / $count;

		if ($this->getHomogeneous()) {
			foreach ($this->pBlocks as $block) {
				$block->setWidth($contentW);
			}
		} else {
			foreach ($this->pBlocks as $block) {
				$contentW = ($w - $used_space) / $count;
				$block->setMaxWidth($contentW);
				$used_space += $block->getWidth() + ($this->getMerge() ? 0 : $this->pSpace);
				$count--;
			}
		}

	/*
		if ($this->getMerge())
			$used_space = 0;
		else
			$this->pSpace;

		$changed = array();
		foreach ($this->pBlocks as $block) {
			$contentW = ($w - $used_space) / $count;
//			echo "I'm already using $used_space px; I can use $contentW pixels for content\n";
			$pre = $block->getWidth();
			$block->setMaxWidth($contentW);

			if ($pre > $block->getWidth())
				$changed[] = $block;

			$used_space += $block->getWidth() + ($this->getMerge() ? 0 : $this->pSpace);
			$count--;
		}

		if ($used_space < $w) {
			if ($this->getMerge())
				$contentW = ($w - $used_space) / count($changed);
 			else
				$contentW = ($w - $used_space - $this->pSpace) / count($changed);

			$count = count($changed);
			foreach ($changed as $block) {
				$block->setMaxWidth($block->getMaxWidth()+$contentW);
			}
		}

		*/
	}

	public function setMinWidth($w) { //TODO complete!
		parent::setMinWidth($w);

		if (!isset($this->pBlocks))
			return;

		foreach ($this->pBlocks as $block) {
			$block->setMinWidth($w);
		}
	}

	public function setMinHeight($h) {
		parent::setMinHeight($h);

		if (!isset($this->pBlocks))
			return;

		foreach ($this->pBlocks as $block) {
			$block->setMinHeight($h);
		}
	}

	public function getImage() {

		$w = $this->getWidth();
		$h = $this->getHeight();

		$box = imagecreate($w, $h);

		$bgcolor = $this->getBgColor();
		$bg = imagecolorallocate($box, $bgcolor['r'], $bgcolor['g'], $bgcolor['b']);

		imagefilledrectangle($box, 0, 0, $w-1, $h-1, $bg);

		$xPos = 0;
		$yPos = 0;

		$xtraX = $this->getMerge() ? (-1)*$this->pBorders : $this->pSpace;

		foreach ($this->pBlocks as $block) {
			//$block->setMaxHeight($this->getHeight); //XXX doesn't work

			imagecopy($box, $block->getImage(), $xPos, $yPos, 0, 0, $block->getWidth(), $block->getHeight());
			$xPos += $block->getWidth() + $xtraX;

		}

		return $box;
	}
}

class VerticalBoxBlock extends ImgBlock {
	private $pBlocks;
	private $pSpace;
	private $pBorders;
	private $pBordersColor; //TODO
	private $pMerge;
	private $pHomogeneous;

	public function VerticalBoxBlock($borders = 0, $space = 0, $merge = false, $homogeneous = false) {
		parent::ImgBlock();
		$this->pBlocks = array();
		$this->pSpace = $space;
		$this->pBorders = $borders;
		$this->pMerge = $merge;
		$this->pHomogeneous = $homogeneous;
	}

	public function addBlock($block) {
		if (!is_subclass_of($block, "ImgBlock"))
			return;

//		if ($block->getWidth() < 1)
//			$block->setWidth($this->getWidth());
//
//		if ($block->getHeight() < 1)
//			$block->setHeight($this->getHeight());

		if ($this->pBorders > 0) {
			$padding = $this->pMerge ? $this->pSpace : 0;
			$block = new BorderedBlock($block, $this->pBorders, $padding);
		}

		$this->pBlocks[] = /*XXX clone  ?*/ $block;

		if ($this->getMaxWidth() > 0)
			$this->setMaxWidth($this->getMaxWidth());
	}

	public function setMerge($m) {
		$this->pMerge = $m;

		if ($this->pMerge && $this->pBorders > 0) {
			foreach ($this->pBlocks as $block)
				$block->setPadding($this->pSpace);
		}
	}

	public function getMerge() {
		return $this->pMerge && $this->pBorders > 0;
	}

	public function setHomogeneous($h) {
		$this->pHomogeneous = $h;
	}

	public function getHomogeneous() {
		return $this->pHomogeneous;
	}

	public function setSpace($s) {
		$this->pSpace = intval($s);
	}

	public function getWidth() {
		$w = 0;
//		$xtra = ($this->getMerge()) ? (-1)*$this->pBorders : $this->pSpace;

		foreach ($this->pBlocks as $block)
			$w = max($w, $block->getWidth());

		return $w/* - $xtra*/;
	}

	public function getContentWidth() {
		return $this->getWidth() - $this->pBorders*2;
	}

	public function getHeight() {
		$h = 0;

		$xtra = ($this->getMerge()) ? (-1)*$this->pBorders : $this->pSpace;

		foreach ($this->pBlocks as $block)
			$h += $block->getHeight() + $xtra;

		return $h - $xtra;
	}

	public function getContentHeight() {
		return $this->getHeight() - $this->pBorders*2;
	}

	public function setMaxWidth($w) {
		parent::setMaxWidth($w);

		if (!isset($this->pBlocks) || $this->getWidth() <= $this->getMaxWidth())
			 return;

		$contentW = ($w - ($this->getMerge() ? 0 : $this->pSpace));

		$this->pBlocks[0]->setWidth($contentW);
		$contentW = $this->pBlocks[0]->getWidth();

//		echo $this->getWidth()."\n";

		for ($i = 1; $i < count($this->pBlocks); $i++) {
			$this->pBlocks[$i]->setWidth($contentW);
		}

//		$newContentW = $this->getWidth();
//
//		if ($newContentW != $contentW) {
//			foreach($this->pBlocks as $block)
//				$block->setWidth($newContentW);
//		}

//		$contentW = ($w - ($this->getMerge() ? 0 : $this->pSpace)) / $count;
//
//		if ($this->getHomogeneous()) {
//			foreach ($this->pBlocks as $block) {
//				$block->setWidth($contentW);
//			}
//		} else {
//			foreach ($this->pBlocks as $block) {
//				$contentW = ($w - $used_space) / $count;
//				$block->setMaxWidth($contentW);
//				$used_space += $block->getWidth() + ($this->getMerge() ? 0 : $this->pSpace);
//				$count--;
//			}
//		}

	/*
		if ($this->getMerge())
			$used_space = 0;
		else
			$this->pSpace;

		$changed = array();
		foreach ($this->pBlocks as $block) {
			$contentW = ($w - $used_space) / $count;
//			echo "I'm already using $used_space px; I can use $contentW pixels for content\n";
			$pre = $block->getWidth();
			$block->setMaxWidth($contentW);

			if ($pre > $block->getWidth())
				$changed[] = $block;

			$used_space += $block->getWidth() + ($this->getMerge() ? 0 : $this->pSpace);
			$count--;
		}

		if ($used_space < $w) {
			if ($this->getMerge())
				$contentW = ($w - $used_space) / count($changed);
 			else
				$contentW = ($w - $used_space - $this->pSpace) / count($changed);

			$count = count($changed);
			foreach ($changed as $block) {
				$block->setMaxWidth($block->getMaxWidth()+$contentW);
			}
		}

		*/
	}

	public function setMinWidth($w) { //TODO complete!
		parent::setMinWidth($w);

		if (!isset($this->pBlocks))
			return;

		foreach ($this->pBlocks as $block) {
			$block->setMinWidth($w);
		}
	}

	public function setMinHeight($h) {
		parent::setMinHeight($h);

		if (!isset($this->pBlocks))
			return;

		foreach ($this->pBlocks as $block) {
			$block->setMinHeight($h);
		}
	}

	public function getImage() {

		$w = $this->getWidth();
		$h = $this->getHeight();

		$box = imagecreate($w, $h);

		$bgcolor = $this->getBgColor();
		$bg = imagecolorallocate($box, $bgcolor['r'], $bgcolor['g'], $bgcolor['b']);

		imagefilledrectangle($box, 0, 0, $w-1, $h-1, $bg);

		$xPos = 0;
		$yPos = 0;

		//$xtraX = $this->getMerge() ? (-1)*$this->pBorders : $this->pSpace;
		$xtraY = $this->getMerge() ? (-1)*$this->pBorders : $this->pSpace;

		foreach ($this->pBlocks as $block) {
			//$block->setMaxHeight($this->getHeight); //XXX doesn't work

			imagecopy($box, $block->getImage(), $xPos, $yPos, 0, 0, $block->getWidth(), $block->getHeight());
			$yPos += $block->getHeight() + $xtraY;
			//$xPos += $block->getWidth() + $xtraX;

		}

		return $box;
	}
}

?>