<?php
namespace Zodream\Image\Base;

interface PointInterface
{
    /**
     * Gets points x coordinate.
     *
     * @return int
     */
    public function getX();

    /**
     * Gets points y coordinate.
     *
     * @return int
     */
    public function getY();

    /**
     * Checks if current coordinate is inside a given box.
     *
     * @param \Imagine\Image\BoxInterface $box
     *
     * @return bool
     */
    public function in(BoxInterface $box);

    /**
     * Returns another point, moved by a given amount from current coordinates.
     *
     * @param int $amount
     *
     * @return \Imagine\Image\PointInterface
     */
    public function move($amount);

    /**
     * Gets a string representation for the current point.
     *
     * @return string
     */
    public function __toString();
}