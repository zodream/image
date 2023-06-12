<?php
declare(strict_types=1);
namespace Zodream\Image\Node;


use Zodream\Image\Adapters\ImageAdapter;
use Zodream\Image\Base\Box;
use Zodream\Image\Base\Font;
use Zodream\Image\Base\FontInterface;
use Zodream\Image\Base\Point;
use Zodream\Image\Image;
use Zodream\Image\ImageManager;

class TextNode extends BaseNode {

    /**
     * @var string
     */
    protected string $content = '';
    /**
     * @var ImageAdapter
     */
    protected ?ImageAdapter $tmpImage = null;
    /**
     * @var FontInterface
     */
    protected ?FontInterface $font = null;

    public function __construct($content) {
        $this->text($content);
    }

    protected function getTmpImage() {
        if (empty($this->tmpImage)) {
            $this->tmpImage = ImageManager::create()->create(new Box(100, 30));
        }
        return $this->tmpImage;
    }

    protected function isWrap() {
        return !array_key_exists('wrap', $this->styles)
            || $this->styles['wrap'] !== false;
    }

    /**
     * @param string $content
     * @return $this
     */
    public function text(string $content) {
        $this->content = $content;
        return $this;
    }

    protected function refreshSize(array $styles, int $parentInnerWidth, array $parentStyles): array
    {
        $innerWidth = !isset($styles['width']) || $styles['width'] === 'auto' ? $parentInnerWidth :
            ($this->styles['width'] - $styles['padding'][1] - $styles['padding'][3]);
        $styles['lineCenter'] = $parentStyles['innerWidth'] / 2 + $parentStyles['x'];
        $styles['font-size'] = NodeHelper::orDefault('font-size', $styles, $parentStyles, 16);
        $styles['lineSpace'] = NodeHelper::orDefault('lineSpace', $styles, $parentStyles, 6);
        $styles['letterSpace'] = NodeHelper::orDefault('letterSpace', $styles, $parentStyles, 0);
        $styles['color'] = NodeHelper::orDefault('color', $styles, $parentStyles, '#333');
        $styles['font'] = NodeHelper::orDefault('font', $styles, $parentStyles, 1);

        if (str_starts_with($styles['font'], '@')) {
            $styles['font'] = $parentStyles[substr($styles['font'], 1)];
        }
        $this->font = new Font($styles['font'], $styles['font-size'],
            $styles['color']);
        $this->computed = $styles;
        list($styles['lines'], $styles['contentWidth']) = $this->getLines($innerWidth);
        if (isset($styles['width']) && $styles['width'] === 'auto') {
            $styles['width'] = $styles['contentWidth'] + $styles['padding'][1] + $styles['padding'][3];
        }
        $styles['height'] = count($styles['lines'])
            * ($styles['font-size'] + $styles['lineSpace']) + $styles['padding'][0] + $styles['padding'][2];
        return parent::refreshSize($styles, $parentInnerWidth, $parentStyles);
    }

    public function draw(Image $box): void {
        // $space = ($this->computed['font-size'] + $this->computed['letterSpace']) / 2;
        $lineSpace = $this->computed['font-size'] + $this->computed['lineSpace'];
        $x = $this->innerX();
        $y = $this->innerY() + $this->computed['font-size'];
        $center = isset($this->computed['center']);
        foreach ($this->computed['lines'] as $line) {
            $startX = $x;
            if ($center) {
                $startX = $this->computed['lineCenter'] - $this->getFontWidth($line) / 2;
            }
            $box->instance()->text($line, $this->font, new Point($startX, $y));
//            foreach ($line as $font) {
//                if (!is_null($font)) {
//                    $box->text($font, $startX, $y, $this->computed['font-size'],
//                        $this->computed['color'], $this->computed['font']);
//                }
//                $startX += $space;
//            }
            $y += $lineSpace;
        }
    }

    protected function getLines(int $maxWidth): array {
        if (!$this->isWrap()) {
            return [
                [$this->content],
                min($this->getFontWidth($this->content), $maxWidth),
            ];
        }
        $lines = [];
        $length = mb_strlen($this->content);
        $start = 0;
        $width = 0;
        for ($i = 1; $i <= $length; $i ++) {
            $line = mb_substr($this->content, $start, $i - $start);
            $w = $this->getFontWidth($line);
            if (
                $w > $maxWidth
            ) {
                $lines[] = mb_substr($this->content, $start, $i - $start - 1);
                $width = $maxWidth;
                $start = $i - 1;
            } elseif ($i === $length) {
                $lines[] = $line;
                $width = max($width, $w);
                break;
            }
        }
        return [$lines, $width];
    }

    protected function getFontWidth($font) {
        $box = $this->getTmpImage()->fontSize($font, $this->font, 0);
        return $box->getWidth();
    }

    /**
     * @param string $content
     * @param array{size: int, color: string, letterSpace: int, lineSpace: int, wrap: bool, font: FontInterface, center: bool} $properties
     * @return TextNode
     */
    public static function create(string $content, array $properties = []) {
        return (new static($content))->setStyles($properties);
    }
}