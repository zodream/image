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
            - $this->computed['margin'][0];
    }

    public function getLeft() {
        return $this->computed['x'] - $this->computed['margin'][3];
    }

    public function getRight() {
        return $this->computed['x'] + $this->computed['width']
            + $this->computed['margin'][1];
    }

    public function getBottom() {
        return $this->computed['y'] + $this->computed['height'] + $this->computed['margin'][2];
    }

    public function innerX() {
        return $this->computed['x']
            + $this->computed['padding'][3];
    }

    public function innerY() {
        return $this->computed['y']
            + $this->computed['padding'][0];
    }

    protected function isFlow() {
        return isset($this->computed['position']) &&
            ($this->computed['position'] === 'absolute' ||
                $this->computed['position'] === 'fixed');
    }

    /**
     * 获取元素在父元素的占位高度
     * @return int
     */
    public function placeholderHeight() {
        return $this->isFlow() ?
            0 : $this->computed['outerHeight'];
    }

    /**
     * 重新计算属性
     * @param array $parentStyles
     */
    public function refresh(array $parentStyles) {
        $styles = $this->styles;
        if (!isset($parentStyles['viewWidth'])) {
            $parentStyles['viewWidth'] = $styles['width'];
            $parentStyles['viewHeight'] = isset($styles['height']) ? $styles['height'] : 0;
        }
        $styles['padding'] = NodeHelper::padding($styles);
        $styles['margin'] = NodeHelper::padding($styles, 'margin');
        $styles['baseX'] = isset($parentStyles['x']) ? $parentStyles['x'] : 0;
        $styles['baseY'] = isset($parentStyles['y']) ? $parentStyles['y'] : 0;
        $copyKeys = ['color', 'font-size', 'font', 'viewWidth', 'viewHeight', 'parentX', 'parentY'];
        foreach ($copyKeys as $key) {
            if (isset($styles[$key])) {
                continue;
            }
            if (isset($parentStyles[$key])) {
                $styles[$key] = $parentStyles[$key];
            }
        }
        $styles = $this->refreshPosition($styles, $parentStyles);
        $parentInnerWidth = isset($parentStyles['innerWidth']) ? $parentStyles['innerWidth'] : $styles['width'];
        $styles = $this->refreshSize($styles, $parentInnerWidth, $parentStyles);
        if (isset($styles['center'])) {
            $styles['x'] = ($parentStyles['outerWidth'] - $styles['width']) / 2;
        }
        $this->computed = $styles;
    }

    protected function refreshPosition(array $styles, array $parentStyles) {
        if (isset($styles['fixed'])) {
            $styles['position'] = 'fixed';
        }
        if (isset($styles['x'])) {
            $styles['x'] +=
                (!isset($styles['position']) || $styles['position'] !== 'fixed'
                    || $styles['position'] !== 'absolute' ? $parentStyles['x'] : 0) + $styles['margin'][1];
        } elseif (isset($styles['margin-left'])) {
            $styles['x'] = (isset($parentStyles['brother']) ?
                    $parentStyles['brother']->getRight() : 0) + $styles['margin'][3];
            $styles['y'] = (isset($parentStyles['brother']) ? $parentStyles['brother']->getTop() : 0) + $styles['margin'][0];
            $styles['position'] = isset($styles['position']) ? $styles['position'] : 'absolute';
        } else {
            $styles['x'] = (isset($parentStyles['x']) ? $parentStyles['x'] : 0)
                + $styles['margin'][1];
        }
        if (!isset($styles['y'])) {
            if (isset($styles['margin-top'])) {
                $styles['y'] = (isset($parentStyles['brother']) ? $parentStyles['brother']->getBottom() : 0)
                    + $styles['margin'][0];
            } else {
                $styles['y'] = (isset($parentStyles['y']) ? $parentStyles['y'] : 0)
                    + $styles['margin'][0];
            }
        } elseif (isset($this->styles['y'])) {
            $styles['y'] += (isset($parentStyles['parentY']) ? $parentStyles['parentY'] : 0)
                + $styles['margin'][0];
        }

        return $styles;
    }

    protected function refreshSize(array $styles, $parentInnerWidth, array $parentStyles) {
        if (isset($styles['width'])) {
            $styles['outerWidth'] = $styles['width'] + $styles['margin'][1] + $styles['margin'][3];
        } else {
            $styles['outerWidth'] = $parentInnerWidth;
            $styles['width'] = $parentInnerWidth - $styles['margin'][1] - $styles['margin'][3];
        }
        $styles['innerWidth'] = $styles['width'] - $styles['padding'][1] - $styles['padding'][3];
        if (isset($styles['height'])) {
            $styles['outerHeight'] = $styles['height'] + $styles['margin'][2]
                + $styles['margin'][0];
            $styles['innerHeight'] = $styles['height'] - $styles['padding'][0] - $styles['padding'][2];
        }
        return $styles;
    }

    /**
     * 绘制元素
     * @param Image|null $box
     * @return mixed
     */
    abstract public function draw(Image $box = null);
}