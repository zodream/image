<?php
namespace Zodream\Image\Node;


use Zodream\Image\Image;

class TextNode {
    /**
     * 设置的属性
     * @var array
     */
    protected $properties = [];

    /**
     * @var string
     */
    protected $content;

    /**
     * 生成的属性
     * @var array
     */
    protected $styles = [];

    public function __construct($content) {
        $this->text($content);
    }


    /**
     * @param array $properties
     * @return $this
     */
    public function setProperties(array $properties) {
        $this->properties = $properties;
        $this->styles = [];
        return $this;
    }

    public function property($name, $value) {
        $this->properties[$name] = $value;
        $this->styles = [];
        return $this;
    }

    protected function isWrap() {
        return !array_key_exists('wrap', $this->properties)
            || $this->properties['wrap'] !== false;
    }

    /**
     * @param $content
     * @return $this
     */
    public function text($content) {
        $this->content = $content;
        return $this;
    }

    public function refresh(array $properties = []) {
        $this->styles = array_merge($this->properties, [
            'y' => $properties['y'],
            'padding' => NodeHelper::padding($this->properties),
            'margin' => NodeHelper::padding($this->properties, 'margin'),
        ]);
        $this->styles['x'] = $properties['x'] + $this->styles['margin'][3] + $this->styles['padding'][3];
        $this->styles['y'] = $properties['y'] + $this->styles['padding'][0] + $this->styles['margin'][0];
        $innerWidth = $properties['innerWidth']
            - $this->styles['margin'][1]
            - $this->styles['margin'][3]
            - $this->styles['padding'][3];
        $this->styles['lineCenter'] = $properties['innerWidth'] / 2 + $properties['x'];
        $this->styles['size'] = NodeHelper::orDefault('size', $this->properties, $properties, 16);
        $this->styles['lineSpace'] = NodeHelper::orDefault('lineSpace', $this->properties, $properties, 6);
        $this->styles['letterSpace'] = NodeHelper::orDefault('letterSpace', $this->properties, $properties, 0);
        $this->styles['color'] = NodeHelper::orDefault('color', $this->properties, $properties, '#333');
        $this->styles['font'] = NodeHelper::orDefault('font', $this->properties, $properties, 1);
        if (strpos($this->styles['font'], '@') === 0) {
            $this->styles['font'] = $properties[substr($this->styles['font'], 1)];
        }
        $this->styles['lines'] = $this->getLines($innerWidth);
        $height = count($this->styles['lines']) * ($this->styles['size'] + $this->styles['lineSpace']);
        return $height + $this->styles['padding'][0] + $this->styles['padding'][2]
            + $this->styles['margin'][0] + $this->styles['margin'][2];
    }

    public function draw(Image $box) {
        $space = ($this->styles['size'] + $this->styles['letterSpace']) / 2;
        $lineSpace = $this->styles['size'] + $this->styles['lineSpace'];
        $x = $this->styles['x'];
        $y = $this->styles['y'] + $this->styles['size'];
        $center = isset($this->styles['center']);
        foreach ($this->styles['lines'] as $line) {
            $startX = $x;
            if ($center) {
                $startX = $this->styles['lineCenter'] - $this->getFontWidth($line) / 2;
            }
            $box->text($line, $startX, $y, $this->styles['size'],
                        $this->styles['color'], $this->styles['font']);
//            foreach ($line as $font) {
//                if (!is_null($font)) {
//                    $box->text($font, $startX, $y, $this->styles['size'],
//                        $this->styles['color'], $this->styles['font']);
//                }
//                $startX += $space;
//            }
            $y += $lineSpace;
        }
    }

    protected function getLines($maxWidth) {
        if (!$this->isWrap()) {
            return [
                $this->content
            ];
        }
        $lines = [];
        $length = mb_strlen($this->content);
        $start = 0;
        for ($i = 1; $i <= $length; $i ++) {
            $line = mb_substr($this->content, $start, $i - $start);
            $w = $this->getFontWidth($line);
            if (
                $w > $maxWidth
            ) {
                $lines[] = mb_substr($this->content, $start, $i - $start - 1);
                $start = $i -1;
            } elseif ($i === $length) {
                $lines[] = $line;
                break;
            }
        }
        return $lines;
    }

    protected function getFontWidth($font) {
        $box = imagettfbbox($this->styles['size'], 0, $this->styles['font'], $font);
        return $box[2];
    }

    public static function create($content, array $properties = []) {
        return (new static($content))->setProperties($properties);
    }
}