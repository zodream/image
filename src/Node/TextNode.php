<?php
namespace Zodream\Image\Node;


use Zodream\Image\Image;

class TextNode extends BaseNode {

    /**
     * @var string
     */
    protected $content;

    public function __construct($content) {
        $this->text($content);
    }



    protected function isWrap() {
        return !array_key_exists('wrap', $this->styles)
            || $this->styles['wrap'] !== false;
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
        $this->styles = array_merge($this->styles, [
            'y' => $properties['y'],
            'padding' => NodeHelper::padding($this->styles),
            'margin' => NodeHelper::padding($this->styles, 'margin'),
        ]);
        $this->styles['x'] = $properties['x'] + $this->styles['margin'][3] + $this->styles['padding'][3];
        $this->styles['y'] = $properties['y'] + $this->styles['padding'][0] + $this->styles['margin'][0];
        $innerWidth = $properties['innerWidth']
            - $this->styles['margin'][1]
            - $this->styles['margin'][3]
            - $this->styles['padding'][3];
        $this->styles['lineCenter'] = $properties['innerWidth'] / 2 + $properties['x'];
        $this->styles['size'] = NodeHelper::orDefault('size', $this->styles, $properties, 16);
        $this->styles['lineSpace'] = NodeHelper::orDefault('lineSpace', $this->styles, $properties, 6);
        $this->styles['letterSpace'] = NodeHelper::orDefault('letterSpace', $this->styles, $properties, 0);
        $this->styles['color'] = NodeHelper::orDefault('color', $this->styles, $properties, '#333');
        $this->styles['font'] = NodeHelper::orDefault('font', $this->styles, $properties, 1);
        if (strpos($this->styles['font'], '@') === 0) {
            $this->styles['font'] = $properties[substr($this->styles['font'], 1)];
        }
        $this->styles['lines'] = $this->getLines($innerWidth);
        $height = count($this->styles['lines']) * ($this->styles['size'] + $this->styles['lineSpace']);
        return $height + $this->styles['padding'][0] + $this->styles['padding'][2]
            + $this->styles['margin'][0] + $this->styles['margin'][2];
    }

    public function draw(Image $box = null) {
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
        return (new static($content))->setStyles($properties);
    }
}