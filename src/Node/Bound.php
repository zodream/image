<?php
namespace Zodream\Image\Node;

class Bound extends Point {

    public $width;

    public $height;

    public function __construct($x, $y, $width, $height) {
        parent::__construct($x, $y);
        $this->width = $width;
        $this->height = $height;
    }
}