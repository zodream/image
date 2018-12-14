<?php
namespace Zodream\Image\Node;

class Box extends Bound {
    const Top = 1;
    const RightTop = 2;
    const Right = 5;
    const RightBottom = 8;
    const Bottom = 7;
    const LeftBottom = 6;
    const Left = 3;
    const LeftTop = 0;
    const Center = 4;

    public $direction = self::LeftTop;

    public function __construct($x, $y, $width, $height, $direction = self::LeftTop) {
        parent::__construct($x, $y, $width, $height);
        $this->direction = $direction;
    }
}