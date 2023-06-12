<?php
declare(strict_types=1);
namespace Zodream\Image\Node;

use Zodream\Image\Base\Point;
use Zodream\Image\Image;

class RectNode extends BaseNode {

    public function draw(Image $box): void {
        $box->instance()->rectangle(new Point($this->computed['x'], $this->computed['y'] + $this->computed['height']),
         new Point($this->computed['x'] + $this->computed['width'], $this->computed['y']), $this->computed['color']);
    }

    /**
     * @param array{points: array[], color: string} $properties
     * @return RectNode
     */
    public static function create(array $properties) {
        return (new static())->setStyles($properties);
    }
}