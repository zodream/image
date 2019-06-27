<?php
namespace Zodream\Image\Node;

use Zodream\Image\Image;
use Zodream\Image\ImageStatic;

class ImgNode {
    /**
     * 设置的属性
     * @var array
     */
    protected $properties = [];

    /**
     * @var string
     */
    protected $src;

    /**
     * @var Image
     */
    protected $image;

    /**
     * 生成的属性
     * @var array
     */
    protected $styles = [];

    public function __construct($src) {
        $this->setSrc($src);
    }


    /**
     * @param array $properties
     * @return ImgNode
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

    /**
     * @param string $src
     * @return ImgNode
     */
    public function setSrc($src) {
        $this->src = $src;
        $this->image = null;
        return $this;
    }

    public function refresh(array $properties = []) {
        if (empty($this->image)) {
            $this->image = ImageStatic::make($this->src);
        }
        $this->styles = [
            'x' => $properties['x'],
            'y' => $properties['y'],
            'padding' => NodeHelper::padding($this->properties),
            'margin' => NodeHelper::padding($this->properties, 'margin'),
        ];
        $this->styles['width'] = $this->getWidth($properties);
        $this->styles['height'] = $this->getHeight($properties);
        if (isset($this->properties['fixed'])) {
            $this->styles['x'] = $this->properties['x'];
            $this->styles['y'] = $this->properties['y'];
        }
        if (isset($this->properties['center'])) {
            $this->styles['x'] = ($properties['outerWidth'] - $this->styles['width']) / 2;
        }
        $this->styles['outerHeight'] = $this->styles['height']
            + $this->styles['padding'][0] + $this->styles['padding'][2]
            + $this->styles['margin'][0] + $this->styles['margin'][2];
        return isset($this->properties['fixed']) ? 0 : $this->outerHeight();
    }

    public function outerHeight() {
        return $this->styles['outerHeight'];
    }

    protected function getWidth(array $properties) {
        $width = NodeHelper::width(isset($this->properties['width']) ? $this->properties['width'] : null, $properties);
        if (!empty($width)) {
            return $width;
        }
        return $this->image->getWidth();
    }

    protected function getHeight(array $properties) {
        $width = NodeHelper::width(isset($this->properties['height']) ? $this->properties['height'] : null, $properties);
        if (!empty($width)) {
            return $width;
        }
        return $this->image->getHeight();
    }

    public function draw(Image $box) {
        $box->copyFromWithResize($this->image, 0, 0,
            $this->styles['x'], $this->styles['y'],
            0, 0, $this->styles['width'], $this->styles['height']);
    }

    public static function create($src, array $properties = []) {
        return (new static($src))->setProperties($properties);
    }
}