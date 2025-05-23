<?php
declare(strict_types=1);
namespace Zodream\Image\Base;

interface BoxInterface
{
    /**
     * Gets box height.
     *
     * @return int
     */
    public function getHeight(): int;

    /**
     * Gets box width.
     *
     * @return int
     */
    public function getWidth(): int;

    /**
     * Creates new BoxInterface instance with ratios applied to both sides.
     *
     * @param float $ratio
     *
     * @return static
     */
    public function scale(float|int $ratio);

    /**
     * Creates new BoxInterface, adding given size to both sides.
     *
     * @param int $size
     *
     * @return static
     */
    public function increase(int $size);

    /**
     * Checks whether current box can fit given box at a given start position,
     * start position defaults to top left corner xy(0,0).
     *
     * @param BoxInterface $box
     * @param null|PointInterface $start
     *
     * @return bool
     */
    public function contains(BoxInterface $box, PointInterface|null $start = null): bool;

    /**
     * Gets current box square, useful for getting total number of pixels in a
     * given box.
     *
     * @return int
     */
    public function square(): int;

    /**
     * Returns a string representation of the current box.
     *
     * @return string
     */
    public function __toString(): string;

    /**
     * Resizes box to given width, constraining proportions and returns the new box.
     *
     * @param int $width
     *
     * @return static
     */
    public function widen(int $width);

    /**
     * Resizes box to given height, constraining proportions and returns the new box.
     *
     * @param int $height
     *
     * @return static
     */
    public function heighten(int $height);
}
