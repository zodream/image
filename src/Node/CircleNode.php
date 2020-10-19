<?php
namespace Zodream\Image\Node;

use Zodream\Image\Base\Point;
use Zodream\Image\Image;

class CircleNode extends BaseNode {

    public function placeholderHeight() {
        return $this->isFlow() ?
            0 :  ($this->styles['y'] + $this->computed['radius']);
    }

    public function draw(Image $box = null) {
        if (empty($this->computed['fill'])) {
            $box->instance()->circle(new Point($this->computed['x'], $this->computed['y']), $this->computed['radius'], $this->computed['color'], false);
            return;
        }
        if (is_string($this->computed['fill'])) {
            $box->instance()->circle(new Point($this->computed['x'], $this->computed['y']),
                $this->computed['radius'], $this->computed['fill'], true);
            return;
        }
        $node = $this->computed['fill'];
        $width = $this->computed['radius'] * 2;
        if ($node instanceof ImgNode) {
            $image = clone $node->getImage();
            $size = $image->getSize();
            if ($image->getWidth() > $image->getHeight()) {
                $image->scale($size->widen($width));
            } else {
                $image->scale($size->heighten($width));
            }
            $r = $this->computed['radius'];
            $startX = $this->computed['x'] - $r;
            $startY = $this->computed['y'] - $r;
            for ($x = 0; $x < $width; $x ++) {
                for ($y = 0; $y < $width; $y ++) {
                    if (pow($x - $r, 2) + pow($y - $r, 2) < pow($r, 2)) {
                        $box->instance()->dot(new Point($x + $startX, $y + $startY),
                            $image->getColorAt(new Point($x, $y)));
                    }
                }
            }
        }
    }

    public static function create($x, $y, $radius, $fill, $color = false, $thickness = 1) {
        return (new static())->setStyles(compact('x', 'y', 'radius', 'fill', 'color', 'thickness'));
    }
}