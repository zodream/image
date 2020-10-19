<?php
namespace Zodream\Image\Node;

use Zodream\Image\Base\Point;
use Zodream\Image\Image;

class LineNode extends BaseNode {
   

    public function refresh(array $properties = []) {
        $this->styles['points'] = isset($this->styles['points']) ? $this->styles['points'] : [];
        if ($this->styles['fixed']) {
            return 0;
        }
        $this->styles['padding'] = NodeHelper::padding($this->styles);
        $this->styles['margin'] = NodeHelper::padding($this->styles, 'margin');
        if (!isset($this->styles['points'][0]) || empty($this->styles['points'][0])) {
            $this->styles['points'][0] = [
                $properties['x'] + $this->styles['margin'][3] + $this->styles['padding'][3],
                $properties['y'] + $this->styles['margin'][0] + $this->styles['padding'][0],
            ];
        }
        $outerHeight = $this->styles['margin'][0] + $this->styles['padding'][0] +
            $this->styles['margin'][2] + $this->styles['padding'][2];
        if (isset($this->styles['points'][1])) {
            return abs($this->styles['points'][0][1] - $this->styles['points'][1][1])
                + $outerHeight;
        }
        if (isset($this->styles['width'])) {
            $width = NodeHelper::width($this->styles['width'], $properties);
            $this->styles['points'][1] = [
                $this->styles['points'][0][0] + $width,
                $this->styles['points'][0][1]
            ];
            return $outerHeight;
        }
        if (isset($this->styles['height'])) {
            $height = NodeHelper::width($this->styles['height'], $properties);
            $this->styles['points'][1] = [
                $this->styles['points'][0][0],
                $this->styles['points'][0][1] + $height
            ];
            return $height + $outerHeight;
        }
        return 0;
    }

    public function draw(Image $box = null) {
        $points = $this->styles['points'];
        for ($i = count($points) - 1; $i > 0; $i --) {
            $box->instance()->line(new Point($points[$i][0], $points[$i][1]),
                new Point($points[$i - 1][0], $points[$i - 1][1]), $this->styles['color']);
        }
    }

    public static function create($x, $y = 0, $x2 = 0, $y2 = 0, array $properties = []) {
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