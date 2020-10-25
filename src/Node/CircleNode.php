<?php
namespace Zodream\Image\Node;

use Zodream\Image\Base\Point;
use Zodream\Image\Image;

class CircleNode extends BaseNode {

    protected function refreshSize(array $styles, $parentInnerWidth, array $parentStyles)
    {
        $styles['width'] = $styles['height'] = 2 * $styles['radius'];
        return parent::refreshSize($styles, $parentInnerWidth, $parentStyles); // TODO: Change the autogenerated stub
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
            if ($image->getWidth() < $image->getHeight()) {
                $image->scale($size->widen($width));
            } else {
                $image->scale($size->heighten($width));
            }
            $r = $this->computed['radius'];
            $startX = $this->computed['x'] - $r;
            $startY = $this->computed['y'] - $r;
            $isBlack = function ($rgb) {
                return $rgb[0] === 0 && $rgb[1] === 0 && $rgb[2] === 0 && $rgb[3] === 0;
            };
            for ($x = 0; $x < $width; $x ++) {
                for ($y = 0; $y < $width; $y ++) {
                    if (pow($x - $r, 2) + pow($y - $r, 2) < pow($r, 2)) {
                        $color = $image->getColorAt(new Point($x, $y));
                        if ($image->getRealType() === 'png' &&
                            $isBlack($image->converterFromColor($color))) {
                            continue;
                        }
                        $box->instance()->dot(new Point($x + $startX, $y + $startY),
                            $color);
                    }
                }
            }
        }
    }

    public static function create($x, $y, $radius, $fill, $color = false, $thickness = 1) {
        return (new static())->setStyles(compact('x', 'y', 'radius', 'fill', 'color', 'thickness'));
    }
}