<?php
declare(strict_types=1);
namespace Zodream\Image\Base;

interface PointInterface
{
    /**
     * Gets points x coordinate.
     *
     * @return int
     */
    public function getX(): int;

    /**
     * Gets points y coordinate.
     *
     * @return int
     */
    public function getY(): int;

    /**
     * Checks if current coordinate is inside a given box.
     *
     * @param BoxInterface $box
     *
     * @return bool
     */
    public function in(BoxInterface $box): bool;

    /**
     * Returns another point, moved by a given amount from current coordinates.
     *
     * @param int $amount
     *
     */
    public function move(int $amount): PointInterface;

    /**
     * Gets a string representation for the current point.
     *
     * @return string
     */
    public function __toString(): string;
}