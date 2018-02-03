<?php
namespace Zodream\Image\Text;


use Zodream\Disk\File;
use Zodream\Image\Image;
use Zodream\Service\Factory;

/**
 * Class TextBlock
 * @package Zodream\Image
[字体]<>
[字体,颜色]
[颜色]
[s:字体,b:背景,f:字体,c:颜色,h:高,w:宽,l:左边,t:顶部,r:右边,b:底部]
[]>< 文件
 */
class TextBlock {

    const PATTERN = '/^([^\]]*)\]\>([^<]+)\<|^([^\]]*)\]\<([^<]+)\>/';

    protected $fontSize = 14;

    protected $background;

    protected $color = '#000';

    protected $font;

    protected $width;

    protected $height;

    protected $left;

    protected $top;

    protected $right;

    protected $bottom;

    /**
     * @var string|File|Image
     */
    protected $content;

    protected $blocks = [];

    /**
     * @param array $blocks
     * @return TextBlock
     */
    public function setBlocks($blocks) {
        $this->blocks = $blocks;
        return $this;
    }


    /**
     * @param int $size
     * @return TextBlock
     */
    public function setFontSize($size) {
        $this->fontSize = intval($size);
        return $this;
    }

    /**
     * @param mixed $background
     * @return TextBlock
     */
    public function setBackground($background) {
        $this->background = $background;
        return $this;
    }

    /**
     * @param string $color
     * @return TextBlock
     */
    public function setColor($color) {
        $this->color = $color;
        return $this;
    }

    /**
     * @param mixed $font
     * @return TextBlock
     */
    public function setFont($font) {
        $this->font = $font;
        return $this;
    }

    /**
     * @param mixed $width
     * @return TextBlock
     */
    public function setWidth($width) {
        $this->width = $width;
        return $this;
    }

    /**
     * @param mixed $height
     * @return TextBlock
     */
    public function setHeight($height) {
        $this->height = $height;
        return $this;
    }

    /**
     * @param mixed $left
     * @return TextBlock
     */
    public function setLeft($left) {
        $this->left = $left;
        return $this;
    }

    /**
     * @param mixed $top
     * @return TextBlock
     */
    public function setTop($top) {
        $this->top = $top;
        return $this;
    }

    /**
     * @param mixed $right
     * @return TextBlock
     */
    public function setRight($right) {
        $this->right = $right;
        return $this;
    }

    /**
     * @param mixed $bottom
     * @return TextBlock
     */
    public function setBottom($bottom) {
        $this->bottom = $bottom;
        return $this;
    }

    /**
     * @param string|File $content
     * @return TextBlock
     */
    public function setContent($content) {
        $this->content = $content;
        return $this;
    }

    public function isEmpty() {
        return empty($this->content);
    }

    public function appendContent($content) {
        $this->content .= $content;
        return $this;
    }

    public function setBoxSize($width, $height) {
        return $this->setWidth($width)->setHeight($height);
    }

    public function setBoxBound($x, $y, $width, $height) {
        return $this->setLeft($x)->setTop($y)->setBoxSize($width, $height);
    }

    public function setProperty($tag, $value) {
        if ($tag == 's' || $tag == 'size' || $tag == 'font-size') {
            return $this->setFontSize($value);
        }

        if ($tag == 'b' || $tag == 'background') {
            return $this->setBackground($value);
        }

        if ($tag == 'c' || $tag == 'color' || $tag == 'foreground') {
            return $this->setColor($value);
        }

        if ($tag == 'f' || $tag == 'font' || $tag == 'font-family') {
            return $this->setFont($value);
        }

        if ($tag == 'w' || $tag == 'width') {
            return $this->setWidth($value);
        }

        if ($tag == 'h' || $tag == 'height') {
            return $this->setHeight($value);
        }

        if ($tag == 'l' || $tag == 'x' || $tag == 'left') {
            return $this->setLeft($value);
        }

        if ($tag == 't' || $tag == 'y' || $tag == 'top') {
            return $this->setTop($value);
        }

        if ($tag == 'r' || $tag == 'right') {
            return $this->setRight($value);
        }

        if ($tag == 'bottom') {
            return $this->setRight($value);
        }
        return $this;
    }


    public function parseProperty($property) {
        if (empty($property)) {
            return $this;
        }
        $args = explode(',', $property);
        $count = count($args);
        if (strpos($property, ':') > 0) {
            foreach ($args as $item) {
                $this->setProperty(...explode(':', $item, 2));
            }
            return $this;
        }
        if ($count == 1) {
            return is_numeric($property)
                ? $this->setFontSize($property)
                : $this->setColor($property);
        }
        if ($count == 2) {
            return $this->setFontSize($args[0])->setColor($args[1]);
        }
        return $this->setBoxBound(...$args);
    }

    public function parseImageProperty($property) {
        if (empty($property)) {
            return $this;
        }
        $args = explode(',', $property);
        $count = count($args);
        if (strpos($property, ':') > 0) {
            foreach ($args as $item) {
                $this->setProperty(...explode(':', $item, 2));
            }
            return $this;
        }
        if ($count == 1) {
            return $this->setWidth($property);
        }
        if ($count == 2) {
            return $this->setBoxSize(...$args);
        }
        return $this->setBoxBound(...$args);
    }

    public static function parse($content) {
        $blocks = [];
        $block = new static();
        $i = -1;
        foreach (explode('[', $content) as $item) {
            $i ++;
            if ($i < 1) {
                $block->setContent($item);
                continue;
            }
            if (!preg_match(self::PATTERN, $item, $match)) {
                // 还需要判断一次
                $block->appendContent('['.$item);
                continue;
            }
            if (!$block->isEmpty()) {
                $blocks[] = $block;
            }
            $block = new static();
            if (empty($match[1])) {
                $block->parseProperty($match[3])
                    ->setContent($match[4]);
            } else {
                $block->parseImageProperty($match[1])
                    ->setContent(Factory::root()->file($match[2]));
            }
            $blocks[] = $block;
            $block = new static();
        }
        return $blocks;
    }
}