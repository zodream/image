<?php
namespace Zodream\Image\Base;


final class Point implements PointInterface {
    /**
     * @var int
     */
    private $x;

    /**
     * @var int
     */
    private $y;

    public function __construct(int $x, int $y) {
        $this->x = $x;
        $this->y = $y;
    }

    public function getX(): int {
        return $this->x;
    }

    public function getY(): int {
        return $this->y;
    }

    public function in(BoxInterface $box) {
        return $this->x < $box->getWidth() && $this->y < $box->getHeight();
    }

    public function move($amount) {
        return new self($this->x + $amount, $this->y + $amount);
    }

    public function __toString() {
        return sprintf('(%d, %d)', $this->x, $this->y);
    }
}