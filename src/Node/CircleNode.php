<?php
namespace Zodream\Image\Node;

use Zodream\Image\Image;

class CircleNode extends BaseNode {

    public function placeholderHeight() {
        return $this->isFlow() ?
            0 :  ($this->styles['y'] + $this->computed['radius']);
    }

    public function draw(Image $box = null) {
        $width = $this->computed['radius'] * 2;
        if (empty($this->computed['fill'])) {
            imagesetthickness($box->image, $this->computed['thickness']);
            imageellipse($box->image, $this->computed['x'], $this->computed['y'],
                $width, $width,
            $box->getColorWithRGB($this->computed['color']));
            return;
        }
        if (is_string($this->computed['fill'])) {
            imagefilledellipse($box->image, $this->computed['x'], $this->computed['y'],
                $width, $width,
                $box->getColorWithRGB($this->computed['fill']));
            return;
        }
        $node = $this->computed['fill'];
        if ($node instanceof ImgNode) {
            $image = $node->getImage();
            if ($image->getWidth() > $image->getHeight()) {
                $image->scaleByHeight($this->computed['radius'] * 2);
            } else {
                $image->scaleByWidth($this->computed['radius'] * 2);
            }
            $r = $this->computed['radius'];
            $startX = $this->computed['x'] - $r;
            $startY = $this->computed['y'] - $r;
            for ($x = 0; $x < $width; $x ++) {
                for ($y = 0; $y < $width; $y ++) {
                    if (pow($x - $r, 2) + pow($y - $r, 2) < pow($r, 2)) {
                        $box->setColor($x + $startX, $y + $startY, $image->getColor($x, $y));
                    }
                }
            }
        }
    }

    public static function create($x, $y, $radius, $fill, $color = false, $thickness = 1) {
        return (new static())->setStyles(compact('x', 'y', 'radius', 'fill', 'color', 'thickness'));
    }
}