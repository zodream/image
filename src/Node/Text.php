<?php
namespace Zodream\Image\Node;

class Text extends Box {

    public $content;

    public $size;

    public $color;

    public $font;

    public function __construct($content, $x, $y, $color, $font, $size, int $direction = self::LeftTop) {
        parent::__construct($x, $y, mb_strlen($content) * $size, $size, $direction);
        $this->content = $content;
        $this->color = $color;
        $this->size = $size;
        $this->font = $font;
    }

}