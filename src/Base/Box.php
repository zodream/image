<?php
declare(strict_types=1);
namespace Zodream\Image\Base;

use InvalidArgumentException;

final class Box implements BoxInterface
{
    /**
     * @var int
     */
    private int $width;

    /**
     * @var int
     */
    private int $height;

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

    public function getWidth(): int {
        return $this->width;
    }

    public function getHeight(): int {
        return $this->height;
    }

    public function scale(float|int $ratio) {
        $width = max(1, round($ratio * $this->width));
        $height = max(1, round($ratio * $this->height));

        return new self($width, $height);
    }

    public function increase(int $size) {
        return new self($size + $this->width,  $size + $this->height);
    }

    public function contains(BoxInterface $box, ?PointInterface $start = null): bool {
        $start = $start ? $start : new Point(0, 0);

        return $start->in($this) && $this->width >= $box->getWidth() + $start->getX() && $this->height >= $box->getHeight() + $start->getY();
    }

    public function square(): int {
        return $this->width * $this->height;
    }

    public function __toString(): string {
        return sprintf('%dx%d px', $this->width, $this->height);
    }

    public function widen(int $width) {
        return $this->scale($width / $this->width);
    }

    public function heighten(int $height) {
        return $this->scale($height / $this->height);
    }
}
