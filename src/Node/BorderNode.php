<?php
namespace Zodream\Image\Node;

use Zodream\Image\Image;

class BorderNode {
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
     * @return BorderNode
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
        return 0;
    }

    public function draw(Image $box) {

    }

    public static function create(array $properties) {
        return (new static())->setProperties($properties);
    }
}