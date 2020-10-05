<?php
namespace Zodream\Image\Node;

use Zodream\Image\Image;
use Zodream\Image\ImageStatic;

class ImgNode extends BaseNode {

    /**
     * @var string
     */
    protected $src;

    /**
     * @var Image
     */
    protected $image;

    public function __construct($src, array $properties = []) {
        $this->setSrc($src);
        $this->setStyles($properties);
    }

    /**
     * @param string $src
     * @return ImgNode
     */
    public function setSrc(string $src) {
        $this->src = trim($src);
        $this->image = null;
        return $this;
    }

    public function refresh(array $properties = []) {
        if (empty($this->image)) {
            $this->image = ImageStatic::make($this->src);
        }
        $this->computed = [
            'x' => $properties['x'],
            'y' => $properties['y'],
            'padding' => NodeHelper::padding($this->styles),
            'margin' => NodeHelper::padding($this->styles, 'margin'),
        ];
        $this->computed['width'] = $this->getWidth($properties);
        $this->computed['height'] = $this->getHeight($properties);
        if (isset($this->styles['fixed'])) {
            $this->computed['x'] = $this->styles['x'];
            $this->computed['y'] = $this->styles['y'];
        }
        if (isset($this->styles['center'])) {
            $this->computed['x'] = ($properties['outerWidth'] - $this->styles['width']) / 2;
        }
        $this->computed['outerHeight'] = $this->styles['height']
            + $this->styles['padding'][0] + $this->styles['padding'][2]
            + $this->styles['margin'][0] + $this->styles['margin'][2];
        return isset($this->styles['fixed']) ? 0 : $this->outerHeight();
    }

    public function outerHeight() {
        return $this->computed['outerHeight'];
    }

    protected function getWidth(array $properties) {
        if (!isset($this->styles['width'])
            && isset($this->styles['height'])
            && is_numeric($this->styles['height'])) {
            return $this->styles['height'] * $this->image->getWidth()
                / $this->image->getHeight();
        }
        $width = NodeHelper::width(isset($this->styles['width']) ? $this->styles['width'] : null, $properties);
        if (!empty($width)) {
            return $width;
        }
        return $this->image->getWidth();
    }

    protected function getHeight(array $properties) {
        if (!isset($this->styles['height'])
            && isset($this->styles['width'])
            && is_numeric($this->styles['width'])) {
            return $this->styles['width'] * $this->image->getHeight() / $this->image->getWidth();
        }
        $width = NodeHelper::width(isset($this->styles['height']) ? $this->styles['height'] : null, $properties);
        if (!empty($width)) {
            return $width;
        }
        return $this->image->getHeight();
    }

    public function draw(Image $box = null) {
        $box->copyFromWithResize($this->image, 0, 0,
            $this->computed['x'], $this->computed['y'],
            0, 0, $this->computed['width'], $this->computed['height']);
    }

    public static function create($src, array $properties = []) {
        return (new static($src))->setStyles($properties);
    }
}