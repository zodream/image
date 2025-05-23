<?php
declare(strict_types=1);
namespace Zodream\Image\Base;

use InvalidArgumentException;
use OutOfBoundsException;

class Matrix {
    /**
     * The array of elements.
     *
     * @var int[]|float[]
     */
    protected array $elements = array();

    /**
     * The matrix width.
     *
     * @var int
     */
    protected int $width;

    /**
     * The matrix height.
     *
     * @var int
     */
    protected int $height;

    /**
     * The given $elements get arranged as follows: The elements will be set from left to right in a row until the
     * row is full. Then, the next line begins alike and so on.
     *
     * @param float|int $width the matrix width
     * @param float|int $height he matrix height
     * @param int[]|float[] $elements the matrix elements
     */
    public function __construct(float|int $width, float|int $height, array $elements = array())
    {
        $this->width = (int) round($width);
        if ($this->width < 1) {
            throw new InvalidArgumentException('width has to be > 0');
        }
        $this->height = (int) round($height);
        if ($this->height < 1) {
            throw new InvalidArgumentException('height has to be > 0');
        }
        $expectedElements = $width * $height;
        $providedElements = count($elements);
        if ($providedElements > $expectedElements) {
            throw new InvalidArgumentException('there are more provided elements than space in the matrix');
        }
        $this->elements = array_values($elements);
        if ($providedElements < $expectedElements) {
            $this->elements = array_merge(
                $this->elements,
                array_fill($providedElements, $expectedElements - $providedElements, 0)
            );
        }
    }

    /**
     * Get the matrix width.
     *
     * @return int
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * Get the matrix height.
     *
     * @return int
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * Set the value of a cell.
     *
     * @param int $x
     * @param int $y
     * @param int|float $value
     */
    public function setElementAt($x, $y, $value)
    {
        $this->elements[$this->calculatePosition($x, $y)] = $value;
    }

    /**
     * Get the value of a cell.
     *
     * @param int $x
     * @param int $y
     *
     * @return int|float
     */
    public function getElementAt($x, $y)
    {
        return $this->elements[$this->calculatePosition($x, $y)];
    }

    /**
     * Return all the matrix values, as a monodimensional array.
     *
     * @return int[]|float[]
     */
    public function getValueList()
    {
        return $this->elements;
    }

    /**
     * Return all the matrix values, as a bidimensional array (every array item contains the values of a row).
     *
     * @return int[]|float[]
     */
    public function getMatrix()
    {
        return array_chunk($this->elements, $this->getWidth());
    }

    /**
     * Returns a new Matrix instance, representing the normalized value of this matrix.
     *
     * @return static
     */
    public function normalize()
    {
        $values = $this->getValueList();
        $divisor = array_sum($values);
        if ($divisor == 0 || $divisor == 1) {
            return clone $this;
        }
        $normalizedElements = array();
        foreach ($values as $value) {
            $normalizedElements[] = $value / $divisor;
        }

        return new static($this->getWidth(), $this->getHeight(), $normalizedElements);
    }

    /**
     * Calculate the offset position of a cell.
     *
     * @param int $x
     * @param int $y
     *
     *
     * @return int
     */
    protected function calculatePosition($x, $y)
    {
        if (0 > $x || 0 > $y || $this->width <= $x || $this->height <= $y) {
            throw new OutOfBoundsException(sprintf('There is no position (%s, %s) in this matrix', $x, $y));
        }

        return $y * $this->height + $x;
    }
}