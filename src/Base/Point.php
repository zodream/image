<?php
declare(strict_types=1);
namespace Zodream\Image\Base;


final readonly class Point implements PointInterface {

    public function __construct(
        private int $x,
        private int $y) {
    }

    public function getX(): int {
        return $this->x;
    }

    public function getY(): int {
        return $this->y;
    }

    public function in(BoxInterface $box): bool {
        return $this->x < $box->getWidth() && $this->y < $box->getHeight();
    }

    public function move(int $amount): PointInterface {
        return new self($this->x + $amount, $this->y + $amount);
    }

    public function __toString(): string {
        return sprintf('(%d, %d)', $this->x, $this->y);
    }
}