<?php
namespace Zodream\Image\Node;

use Zodream\Image\Image;

class BorderNode extends BaseNode {

    /**
     * @var BaseNode
     */
    protected $content;

    public function __construct($content = null) {
        $this->content = $content;
    }

    protected function refreshSize(array $styles, $parentInnerWidth, array $parentStyles) {
        if (!$this->content) {
            $styles['width'] = isset($styles['width']) ?
                NodeHelper::width($styles['width'], $parentStyles) : $parentInnerWidth;
            $styles['height'] = isset($styles['width']) ?
                NodeHelper::width($styles['height'], $parentStyles) : 1;
        }
        if (!isset($styles['width']) || !isset($styles['height'])) {
            $this->content->style('width', 'auto');
            $this->content->refresh($parentStyles);
            $styles['width'] = isset($styles['width'])
                ? $styles['width'] : $this->content->computedStyle('outerWidth');
        }
        $styles['radius'] = NodeHelper::padding($styles, 'radius');
        return parent::refreshSize($styles, $parentInnerWidth, $parentStyles);
    }

    public function draw(Image $box = null) {
        $startX = $this->computed['x'];
        $startY = $this->computed['y'];
        $endX = $startX + $this->computed['width'];
        $endY = $startY + $this->computed['height'];
        $color = $box->getColorWithRGB($this->computed['color']);
        $radius = $this->computed['radius'];
        if ($radius[0] > 0) {
            // top-left
            $this->radiusLine($box, $color,
                $startX + $radius[0],
                $startY + $radius[0],
                $startX, $startX + $radius[0],
                $startX + $radius[0], $startY, $radius[0]);
        }
        // top
        NodeHelper::step(function ($x) use ($startY, $box, $color) {
            $box->setColor($x, $startY, $color);
        }, $startX + $radius[0], $endX - $radius[1]);
        if ($radius[1] > 0) {
            // top-right
            $this->radiusLine($box, $color,
                $endX - $radius[1],
                $startY + $radius[1],
                $endX - $radius[1], $endY,
                $endX, $startY + $radius[1], $radius[1]);
        }
        // right
        NodeHelper::step(function ($y) use ($endX, $box, $color) {
            $box->setColor($endX, $y, $color);
        }, $startY + $radius[1], $endY - $radius[2]);
        if ($radius[2] > 0) {
            // bottom-right
            $this->radiusLine($box, $color,
                $endX - $radius[2],
                $endY - $radius[2],
                $endX, $endY - $radius[2],
                $endX - $radius[2], $endY, $radius[2]);
        }
        // bottom
        NodeHelper::step(function ($x) use ($endY, $box, $color) {
            $box->setColor($x, $endY, $color);
        }, $startX + $radius[3], $endX - $radius[2]);
        if ($radius[3] > 0) {
            // bottom-right
            $this->radiusLine($box, $color,
                $startX + $radius[3],
                $endY - $radius[3],
                $startX + $radius[3], $endY,
                $startX, $endY - $radius[3], $radius[3]);
        }
        // bottom
        NodeHelper::step(function ($y) use ($startX, $box, $color) {
            $box->setColor($startX, $y, $color);
        }, $startY + $radius[0], $endY - $radius[3]);
    }

    public function radiusLine(Image $box,
                               $color,
                               $centerX,
                               $centerY,
                               $startX, $startY, $endX, $endY, $radius) {
        NodeHelper::step(function ($x) use ($box, $color, $centerX, $centerY, $startY, $endY, $radius) {
            $y = $centerY +
                ($endY > $centerY || $endY > $startY ? 1 : -1) *
                sqrt(pow($radius, 2) - pow(abs($x - $centerX), 2));
            $box->setColor($x, $y, $color);
        }, $startX, $endX);
    }

    public static function create($content, array $properties = []) {
        if (is_array($content)) {
            list($content, $properties) = [null, $content];
        }
        return (new static($content))->setStyles($properties);
    }
}