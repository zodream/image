<?php
namespace Zodream\Image\Node;

use Zodream\Image\Image;

abstract class BaseNode {

    /**
     * 设置的属性
     * @var array
     */
    protected $styles = [];

    /**
     * 生成的属性
     * @var array
     */
    protected $computed = [];


    /**
     * @param array $properties
     * @return static
     */
    public function setStyles(array $properties) {
        $this->styles = $properties;
        $this->computed = [];
        return $this;
    }

    /**
     * 设置属性
     * @param $name
     * @param $value
     * @return $this
     */
    public function style($name, $value) {
        $this->styles[$name] = $value;
        $this->computed = [];
        return $this;
    }

    /**
     * 获取计算完成的属性
     * @param $name
     * @return mixed|null
     */
    public function computedStyle($name) {
        return isset($this->computed[$name]) ? $this->computed[$name] : null;
    }

    public function getTop() {
        return $this->computed['y']
            - $this->computed['padding'][0]
            - $this->computed['margin'][0];
    }

    public function getRight() {
        return $this->computed['x'] + $this->computed['outerWidth']
            - $this->computed['padding'][3]
            - $this->computed['margin'][3];
    }

    /**
     * 获取元素在父元素的占位高度
     * @return int
     */
    public function placeholderHeight() {
        return isset($this->computed['position']) && $this->computed['position'] === 'absolute' ?
            0 : $this->computed['outerHeight'];
    }

    /**
     * 重新计算属性
     * @param array $parentStyle
     * @return mixed
     */
    abstract public function refresh(array $parentStyle);

    /**
     * 绘制元素
     * @param Image|null $box
     * @return mixed
     */
    abstract public function draw(Image $box = null);
}