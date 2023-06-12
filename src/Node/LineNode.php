<?php
declare(strict_types=1);
namespace Zodream\Image\Node;

use Zodream\Image\Base\Point;
use Zodream\Image\Image;

class LineNode extends BaseNode {

    public function refresh(array $parentStyles = []): void {
        $this->styles['points'] = $this->styles['points'] ?? [];
        if ($this->styles['fixed']) {
            $this->computed['placeholderHeight'] = 0;
            return;
        }
        $this->styles['padding'] = NodeHelper::padding($this->styles);
        $this->styles['margin'] = NodeHelper::padding($this->styles, 'margin');
        if (!isset($this->styles['points'][0]) || empty($this->styles['points'][0])) {
            $this->styles['points'][0] = [
                $parentStyles['x'] + $this->styles['margin'][3] + $this->styles['padding'][3],
                $parentStyles['y'] + $this->styles['margin'][0] + $this->styles['padding'][0],
            ];
        }
        $outerHeight = $this->styles['margin'][0] + $this->styles['padding'][0] +
            $this->styles['margin'][2] + $this->styles['padding'][2];
        if (isset($this->styles['points'][1])) {
            $this->computed['placeholderHeight'] = abs($this->styles['points'][0][1] - $this->styles['points'][1][1])
                + $outerHeight;
            return;
        }
        if (isset($this->styles['width'])) {
            $width = NodeHelper::width($this->styles['width'], $parentStyles);
            $this->styles['points'][1] = [
                $this->styles['points'][0][0] + $width,
                $this->styles['points'][0][1]
            ];
            $this->computed['placeholderHeight'] = $outerHeight;
            return;
        }
        if (isset($this->styles['height'])) {
            $height = NodeHelper::width($this->styles['height'], $parentStyles);
            $this->styles['points'][1] = [
                $this->styles['points'][0][0],
                $this->styles['points'][0][1] + $height
            ];
            $this->computed['placeholderHeight'] = $height + $outerHeight;
            return;
        }
        $this->computed['placeholderHeight'] = 0;
    }

    public function draw(Image $box): void {
        $points = $this->styles['points'];
        for ($i = count($points) - 1; $i > 0; $i --) {
            $box->instance()->line(new Point($points[$i][0], $points[$i][1]),
                new Point($points[$i - 1][0], $points[$i - 1][1]), $this->styles['color']);
        }
    }

    /**
     * @param array|int $x
     * @param int $y
     * @param int $x2
     * @param int $y2
     * @param array{size: int, fixed: bool, color: string} $properties
     * @return LineNode
     */
    public static function create(array|int $x, int $y = 0, int $x2 = 0, int $y2 = 0, array $properties = []) {
        if (is_array($x)) {
            $properties = $x;
        } else {
            $properties['points'] = [
                [$x, $y],
                [$x2, $y2]
            ];
        }
        return (new static())
            ->setStyles($properties);
    }

}