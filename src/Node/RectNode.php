<?php
namespace Zodream\Image\Node;

use Zodream\Image\Image;

class RectNode extends BaseNode {

    public function draw(Image $box = null) {
        $box->fill($this->computed['x'], $this->computed['y'] + $this->computed['height'],
         $this->computed['x'] + $this->computed['width'], $this->computed['y'], $this->computed['color']);
    }

    public static function create(array $properties) {
        return (new static())->setStyles($properties);
    }
}