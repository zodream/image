<?php
declare(strict_types=1);
namespace Zodream\Image\Node;

use Zodream\Image\Base\Point;
use Zodream\Image\Image;

class BorderNode extends BaseNode {

    public function __construct(
        protected BaseNode|null $content = null) {
    }

    protected function refreshSize(array $styles, int $parentInnerWidth, array $parentStyles): array {
        if (!$this->content) {
            $styles['width'] = isset($styles['width']) ?
                NodeHelper::width($styles['width'], $parentStyles) : $parentInnerWidth;
            $styles['height'] = isset($styles['width']) ?
                NodeHelper::width($styles['height'], $parentStyles) : 1;
        }
        if (!isset($styles['width']) || !isset($styles['height'])) {
            $this->content->style('width', 'auto');
            $this->content->refresh(array_merge($parentStyles, [
                'x' => $styles['x'] + $styles['padding'][3],
                'y' => $styles['y'] + $styles['padding'][0],
            ]));
            $styles['width'] = $styles['width'] ?? $this->content->computedStyle('outerWidth');
            $styles['height'] = $styles['height'] ?? $this->content->computedStyle('outerHeight');
        }
        $styles['radius'] = NodeHelper::padding($styles, 'radius');
        return parent::refreshSize($styles, $parentInnerWidth, $parentStyles);
    }

    public function draw(Image $box): void {
        $startX = $this->computed['x'];
        $startY = $this->computed['y'];
        $endX = $startX + $this->computed['width'];
        $endY = $startY + $this->computed['height'];
        $color = $box->instance()->converterToColor($this->computed['color']);
        $radius = $this->computed['radius'];
        $each = function ($radius, $cb) {
            if ($radius < 1) {
                return;
            }
            for ($i = 1; $i < $radius; $i ++) {
                $j = $radius - sqrt(pow($radius, 2) - pow(abs($radius - $i), 2));
                $cb($i, intval($j));
            }
        };
        // top-left
        $each($radius[0], function ($i, $j) use ($box, $startX, $startY, $color) {
            $box->instance()->dot(new Point($startX + $i, $startY + $j), $color);
        });
        // top
        $box->instance()->line(new Point($startX + $radius[0], $startY),
            new Point($endX - $radius[1], $startY), $color);
        // top-right
        $each($radius[1], function ($i, $j) use ($box, $endX, $startY, $color) {
            $box->instance()->dot(new Point($endX - $i, $startY + $j), $color);
        });
        // right
        $box->instance()->line(new Point($endX, $startY + $radius[1]),
            new Point($endX, $endY - $radius[2]), $color);
        // bottom-right
        $each($radius[2], function ($i, $j) use ($box, $endX, $endY, $color) {
            $box->instance()->dot(new Point($endX - $i, $endY - $j), $color);
        });
        // bottom
        $box->instance()->line(new Point($endX - $radius[2], $endY),
            new Point($startX + $radius[3], $endY), $color);
        // bottom-left
        $each($radius[3], function ($i, $j) use ($box, $startX, $endY, $color) {
            $box->instance()->dot(new Point($startX + $i, $endY - $j), $color);
        });
        // left
        $box->instance()->line(new Point($startX, $endY - $radius[3]),
            new Point($startX, $startY + $radius[0]), $color);
        $this->content->draw($box);
    }

    /**
     * @param array|BaseNode|null $content
     * @param array{size: int, fixed: bool, margin: int} $properties
     * @return BaseNode
     */
    public static function create(array|BaseNode|null $content, array $properties = []): BaseNode {
        if (is_array($content)) {
            list($content, $properties) = [null, $content];
        }
        return (new static($content))->setStyles($properties);
    }
}