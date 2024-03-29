<?php
declare(strict_types=1);
namespace Zodream\Image\Base;

use InvalidArgumentException;
use Zodream\Image\ImageManager;

class Font implements FontInterface {
    /**
     * @var string
     */
    protected string|int $file;

    /**
     * Constructs a font with specified $file, $size and $color.
     *
     * The font size is to be specified in points (e.g. 10pt means 10)
     *
     * @param string $file
     * @param int $size
     * @param mixed $color
     */
    public function __construct(mixed $file,
                                protected int $size,
                                protected mixed $color)
    {
        $this->file = is_string($file) ? $file : intval($file);
    }

    /**
     * {@inheritdoc}
     *
     */
    final public function getFile()
    {
        return $this->file;
    }

    /**
     * {@inheritdoc}
     *
     */
    final public function getSize(): int
    {
        return $this->size;
    }

    /**
     * {@inheritdoc}
     *
     */
    final public function getColor(): mixed
    {
        return $this->color;
    }

    /**
     * {@inheritdoc}
     *
     */
    public function wrapText(mixed $string, int $maxWidth, int $angle = 0): string
    {
        $string = (string) $string;
        if ($string === '') {
            return $string;
        }
        $maxWidth = (int) round($maxWidth);
        if ($maxWidth < 1) {
            throw new InvalidArgumentException(sprintf('The $maxWidth parameter of wrapText must be greater than 0.'));
        }
        $words = explode(' ', $string);
        $lines = array();
        $currentLine = null;
        foreach ($words as $word) {
            if ($currentLine === null) {
                $currentLine = $word;
            } else {
                $testLine = $currentLine . ' ' . $word;
                $testBox = $this->box($testLine, $angle);
                if ($testBox->getWidth() <= $maxWidth) {
                    $currentLine = $testLine;
                } else {
                    $lines[] = $currentLine;
                    $currentLine = $word;
                }
            }
        }
        if ($currentLine !== null) {
            $lines[] = $currentLine;
        }

        return implode("\n", $lines);
    }

    public function box(string $string, int $angle = 0): Box {
        return ImageManager::create()->fontSize($string, $this, $angle);
    }
}