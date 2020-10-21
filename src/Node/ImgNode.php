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

    public function refreshAsBackground(array $parentStyles) {
        if (isset($this->styles['full'])) {
            $this->computed['width'] = $parentStyles['width'];
            $this->computed['height'] = $parentStyles['height'];
            return;
        }
        if (isset($this->styles['width']) && strpos($this->styles['width'], '%')) {
            $this->computed['width'] = NodeHelper::percentage($parentStyles['width'], $this->styles['width']);
        }
        if (isset($this->styles['height']) && strpos($this->styles['height'], '%')) {
            $this->computed['height'] = NodeHelper::percentage($parentStyles['height'], $this->styles['height']);
        }
    }

    public function outerHeight() {
        return $this->computed['outerHeight'];
    }

    protected function getWidth(array $properties) {
        if (!isset($this->styles['width'])
            && isset($this->styles['height'])
            && is_numeric($this->styles['height'])) {
            return $this->styles['height'] * $this->getImage()->getWidth()
                / $this->getImage()->getHeight();
        }
        $width = NodeHelper::width(isset($this->styles['width']) ? $this->styles['width'] : null, $properties);
        if (!empty($width)) {
            return $width;
        }
        return $this->getImage()->getWidth();
    }

    protected function getHeight(array $properties) {
        if (!isset($this->styles['height'])
            && isset($this->styles['width'])
            && is_numeric($this->styles['width'])) {
            return $this->styles['width'] * $this->getImage()->getHeight() / $this->getImage()->getWidth();
        }
        $width = NodeHelper::width(isset($this->styles['height'])
            ? $this->styles['height'] : null, $properties, 'height');
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