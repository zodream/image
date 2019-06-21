<?php
namespace Zodream\Image\Node;

use Zodream\Image\Image;

class LineNode {
    /**
     * 设置的属性
     * @var array
     */
    protected $properties = [];

    /**
     * 生成的属性
     * @var array
     */
    protected $styles = [];


    /**
     * @param array $properties
     * @return LineNode
     */
    public function setProperties(array $properties) {
        $this->properties = $properties;
        $this->styles = [];
        return $this;
    }

    public function property($name, $value) {
        $this->properties[$name] = $value;
        $this->styles = [];
        return $this;
    }

    public function refresh(array $properties = []) {
        $this->styles['color'] = $this->properties['color'];
        $this->styles['points'] = isset($this->properties['points']) ? $this->properties['points'] : [];
        if ($this->properties['fixed']) {
            return 0;
        }
        $this->styles['padding'] = NodeHelper::padding($this->properties);
        $this->styles['margin'] = NodeHelper::padding($this->properties, 'margin');
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
        if (isset($this->properties['width'])) {
            $width = NodeHelper::width($this->properties['width'], $properties);
            $this->styles['points'][1] = [
                $this->styles['points'][0][0] + $width,
                $this->styles['points'][0][1]
            ];
            return $outerHeight;
        }
        if (isset($this->properties['height'])) {
            $height = NodeHelper::width($this->properties['height'], $properties);
            $this->styles['points'][1] = [
                $this->styles['points'][0][0],
                $this->styles['points'][0][1] + $height
            ];
            return $height + $outerHeight;
        }
        return 0;
    }

    public function draw(Image $box) {
        $points = $this->styles['points'];
        for ($i = count($points) - 1; $i > 0; $i --) {
            $box->line($points[$i][0], $points[$i][1], $points[$i - 1][0], $points[$i - 1][1], $this->styles['color']);
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
            ->setProperties($properties);
    }

}