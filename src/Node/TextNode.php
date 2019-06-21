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
        $this->styles['lineSpace'] = NodeHelper::orDefault('lineSpace', $this->properties, $properties, 0);
        $this->styles['letterSpace'] = NodeHelper::orDefault('letterSpace', $this->properties, $properties, 0);
        $this->styles['color'] = NodeHelper::orDefault('color', $this->properties, $properties, '#333');
        $this->styles['font'] = NodeHelper::orDefault('font', $this->properties, $properties, 1);
        $this->styles['lines'] = $this->getLines(floor($innerWidth / ($this->styles['size'] + $this->styles['letterSpace'])));
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
                $startX = $this->styles['lineCenter'] - count($line) * $space / 2;
            }
            foreach ($line as $font) {
                if (!is_null($font)) {
                    $box->text($font, $startX, $y, $this->styles['size'],
                        $this->styles['color'], $this->styles['font']);
                }
                $startX += $space;
            }
            $y += $lineSpace;
        }
    }

    protected function getLines($maxCount) {
        if (array_key_exists('wrap', $this->properties)
            && $this->properties['wrap'] === false) {
            $line = [$this->content];
            for ($i = mb_strlen($this->content) - 1; $i > 0; $i --) {
                $line[] = null;
            }
            return [
                $line
            ];
        }
        $maxCount *= 2;
        $lines = [];
        $line = [];
        $length = mb_strlen($this->content);
        for ($i = 0; $i < $length; $i ++) {
            if (count($line) >= $maxCount) {
                $lines[] = $line;
                $line = [];
            }
            $font = mb_substr($this->content, $i, 1);
            $code = ord($font);
            if ($code === 9) {
                $line[] = null;
                $line[] = null;
                $line[] = null;
                $line[] = null;
                continue;
            }
            if ($code === 10) {
                $lines[] = $line;
                $line = [];
                continue;
            }
            if ($code <= 127) {
                $line[] = $font;
                continue;
            }
            if (count($line) + 2 > $maxCount) {
                $lines[] = $line;
                $line = [];
            }
            $line[] = $font;
            $line[] = null;
        }
        $lines[] = $line;
        return $lines;
    }

    public static function create($content, array $properties = []) {
        return (new static($content))->setProperties($properties);
    }
}