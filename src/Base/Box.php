<?php
namespace Zodream\Image\Base;

use InvalidArgumentException;

final class Box implements BoxInterface
{
    /**
     * @var int
     */
    private $width;

    /**
     * @var int
     */
    private $height;

    public function __construct($width, $height) {
        if (!\is_int($width)) {
            $width = (int) round($width);
        }
        if (!\is_int($height)) {
            $height = (int) round($height);
        }
        $this->width = $width;
        $this->height = $height;
        if ($this->width < 1 || $this->height < 1) {
            throw new InvalidArgumentException(sprintf('Length of either side cannot be 0 or negative, current size is %sx%s', $width, $height));
        }
    }

    public function getWidth() {
        return $this->width;
    }

    public function getHeight() {
        return $this->height;
    }

    public function scale($ratio) {
        $width = max(1, round($ratio * $this->width));
        $height = max(1, round($ratio * $this->height));

        return new self($width, $height);
    }

    public function increase($size) {
        return new self((int) $size + $this->width, (int) $size + $this->height);
    }

    public function contains(BoxInterface $box, PointInterface $start = null) {
        $start = $start ? $start : new Point(0, 0);

        return $start->in($this) && $this->width >= $box->getWidth() + $start->getX() && $this->height >= $box->getHeight() + $start->getY();
    }

    public function square() {
        return $this->width * $this->height;
    }

    public function __toString() {
        return sprintf('%dx%d px', $this->width, $this->height);
    }

    public function widen($width) {
        return $this->scale($width / $this->width);
    }

    public function heighten($height) {
        return $this->scale($height / $this->height);
    }
}
