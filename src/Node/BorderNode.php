<?php
namespace Zodream\Image\Node;

use Zodream\Image\Image;

class BorderNode extends BaseNode {

    protected $content;

    public function __construct($content = null) {
        $this->content = $content;
    }



    public function refresh(array $properties = []) {

    }

    public function draw(Image $box = null) {

    }

    public static function create($content, array $properties = []) {
        if (is_array($content)) {
            list($content, $properties) = [null, $content];
        }
        return (new static($content))->setStyles($properties);
    }
}