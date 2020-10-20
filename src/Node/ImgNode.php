<?php
namespace Zodream\Image\Node;

use Zodream\Image\Adapters\ImageAdapter;
use Zodream\Image\Base\Box;
use Zodream\Image\Base\Point;
use Zodream\Image\Image;
use Zodream\Image\ImageManager;

class ImgNode extends BaseNode {

    /**
     * @var string
     */
    protected $src;

    /**
     * @var ImageAdapter
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

    public function getImage() {
        if (empty($this->image)) {
            $this->image = ImageManager::create()->open($this->src);
        }
        return $this->image;
    }

    protected function refreshSize(array $styles, $parentInnerWidth, array $parentStyles) {
        $styles['width'] = $this->getWidth($parentStyles);
        $styles['height'] = $this->getHeight($parentStyles);
        return parent::refreshSize($styles, $parentInnerWidth, $parentStyles);
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
        return $this->getImage()->getHeight();
    }

    public function draw(Image $box = null) {
        $img = $this->getImage();
        if ($img->getWidth() != $this->computed['width'] || $img->getHeight() != $this->computed['height']) {
            $img = clone $this->getImage();
            $img->scale(new Box($this->computed['width'], $this->computed['height']));
        }
        $box->instance()->paste($img, new Point($this->computed['x'], $this->computed['y']));
    }

    public static function create($src, array $properties = []) {
        return (new static($src))->setStyles($properties);
    }
}