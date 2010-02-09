<?

interface PMGraph {
	public function getType();
	public function setWidth($w);
	public function setHeight($h);
	public function getWidth();
	public function getHeight();
	public function draw($format = "png", $file = null);
	public function getImage();
}
