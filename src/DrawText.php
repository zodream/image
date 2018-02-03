<?php
namespace Zodream\Image;


use Zodream\Image\Text\TextLine;

class DrawText extends Image {

    protected $lines = [];

    public function __construct($content = null) {
        $this->lines = TextLine::parse($content);

    }

}