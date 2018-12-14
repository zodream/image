<?php
namespace Zodream\Image\Node;

class Point {
    public $x;

    public $y;

    public function __construct($x, $y) {
        $this->x = $x;
        $this->y = $y;
    }
}