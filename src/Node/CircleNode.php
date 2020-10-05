<?php
namespace Zodream\Image\Node;

use Zodream\Image\Image;

class CircleNode extends BaseNode {


    public function refresh(array $properties = []) {

    }

    public function draw(Image $box = null) {

    }

    public static function create(array $properties) {
        return (new static())->setStyles($properties);
    }
}