<?php

include "ImgBlock.class.php";

////////////////////////////////////////////////////////

//$blk = new ColorBlock("#AABBDD");
//$blk->setMaxHeigth(25);
//$blk->setMaxWidth(100);
//$blk->setFgColor("#CCDDEE");
//$blk->setBorder(10);

putenv('GDFONTPATH=' . realpath('../fonts/Droid'));
$font_bold = "DroidSans-Bold.ttf";
$font_normal = "DroidSans.ttf";
$font_size = 12;

$blk = new TextBlock("Testttttttttttttt\n<u>mmmm</u>\ngggpph\nprrr\nfff\nassda\n<u>ssssssss</u>\nff<u>a</u>jslkafd<u>deee</u>", $font_normal, $font_size);
$blk = new BorderedBlock($blk, 5, 10);
//$blk->setMaxWidth(29);
//echo $blk->getWidth()." ".$blk->getContent()->getWidth()."\n";

$blk->setMaxWidth($blk->getWidth()/2);
$blk->setMaxWidth($blk->getWidth()*2);

$blk = new HorizontalBoxBlock(1);
$blk->setSpace(5);
$blk->setMerge(true); //XXX if not merged should use a better align
$blk->setHomogeneous(true);


$a = new TextBlock("12 d", $font_normal, $font_size);
//$a->setMaxWidth(10);
//echo $a->getWidth()."\n";
$b = new TextBlock("<u>40 ph</u>", $font_normal, $font_size);
$c = new TextBlock("<u>1350 €</u>", $font_normal, $font_size);
$d = new TextBlock("uhuhuhuhuh", $font_normal, $font_size);
$f = new TextBlock("mmmmmm", $font_normal, $font_size);
//print_r($f->getTextBox());
$e = new TextBlock("prrr", $font_normal, $font_size);
$g = new TextBlock("gggph", $font_normal, $font_size);
$h = new ColorBlock("#bcd");
$i = new ColorBlock("#fff");
$cbk = new HorizontalBoxBlock();
$cbk->addBlock($h);
$cbk->addBlock($i);
$cbk2 = new HorizontalBoxBlock(1);
$cbk2->addBlock($cbk);
$cbk = $cbk2;

$blk->setWidth(420);
$blk->addBlock($a);
$blk->addBlock($b);
$blk->addBlock($c);
$blk->addBlock($d);
$blk->addBlock($f);
$blk->addBlock($g);
//$blk->addBlock($e);

///blk = new BorderedBlock($blk, 5, 0);
///blk->setBorderColor("#BBBBBB");
//$blk->setMaxWidth(300);
//print_r($blk);
//
//$blk = new BorderedBlock($blk, 5, 0);
//$blk->setBorderColor("#333333");
//
//$blk = new BorderedBlock($blk, 5, 0);
//$blk->setBorderColor("#ff00ff");


//$blkV = new VerticalBoxBlock(0);
//$blkV->setSpace(-1);
////$blkV->setMerge(true);
/////blkV->setHomogeneous(true);
//$blk2 = clone $blk;
//$blkV->addBlock($blk2);
//$blk2->addBlock(clone $a);
////$blkV->addBlock(clone $blk);
//$blkV->addBlock(clone $g);
//$blkV->addBlock(clone $f);
////$blkV->addBlock(clone $c);
//$h->setWidth($blkV->getContentWidth());
//$h->setHeight(20);
//$blkV->addBlock(clone $cbk);
//$blkV->addBlock(clone $blk);
//$blkV->addBlock(clone $blk);
//$blkV->addBlock(clone $blk);
//$blkV->setMaxWidth(390);
//$blk = $blkV;


$f = new TextBlock("mmmYmmm", $font_normal, $font_size);

$blk = new HorizontalBoxBlock(2);
$blk->setHomogeneous(true);
$blk->setSpace(1);
$blk->setMerge(true);
$blk->setWidth(184);
$blk->addBlock($f);
$blk->addBlock(clone $f);
$old = $blk;

$f = new TextBlock("Δ!", $font_normal, 20);
$f->setAlign("center");
//$f = new TextBlock("m\n\nm\nm\nYm\nmm\n\n", $font_normal, 24);
$f->setBgColor("#ffff");

$crc= new CircleBlock($f);
//$crc->setFgColor("#FF1122");
//$crc->setBgColor("#11FF66");
$crc->setPadding(5);
$crc->setBorder(2);

$blk = new FixedBlock();
$blk->addContent($old, 0, 0);
$blk->addContent(clone $crc, 5, 5);
$blk->addContent(clone $crc, 10, 10);
$blk->addContent(clone $crc, 100, 100);
$blk->setBgColor("#f13");
$blk->setWidth(150);

header("Content-type: image/png");
imagepng($blk->getImage());
//imagepng($blk->getImage());




?>