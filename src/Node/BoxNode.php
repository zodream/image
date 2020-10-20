<?php
namespace Zodream\Image\Node;

use Zodream\Image\Adapters\ImageAdapter;
use Zodream\Image\Base\Box;
use Zodream\Image\Base\Point;
use Zodream\Image\Image;
use Zodream\Image\ImageManager;

class BoxNode extends BaseNode {

    /**
     * @var BaseNode[]
     */
    protected $children = [];

    public function append(...$nodes) {
        if (func_num_args() === 1 && is_array($nodes[0])) {
            $nodes = $nodes[0];
        }
        $this->children = array_merge($this->children, $nodes);
        $this->computed = [];
        return $this;
    }

    protected function refreshSize(array $styles, $parentInnerWidth, array $parentStyles) {
        $styles = parent::refreshSize($styles, $parentInnerWidth, $parentStyles);
        $styles['parentX'] = $styles['x'];
        $styles['parentY'] = $styles['y'];
        $parentStyles = array_merge($parentStyles, $styles, [
            'x' => $styles['x'] + $styles['padding'][3],
            'y' => $styles['y'] + $styles['padding'][0],
        ]);
        $oldY = $parentStyles['y'];
        $maxY = $oldY;
        foreach ($this->children as $node) {
            $node->refresh($parentStyles);
            $parentStyles['y'] += $node->placeholderHeight();
            $parentStyles['brother'] = $node;
            $maxY = max($maxY, $node->getBottom());
        }
        if (!isset($this->styles['height'])) {
            $maxY -= $oldY;
            $styles['innerHeight'] = $maxY;
            $styles['height'] = $maxY + $styles['padding'][2] + $styles['padding'][3];
            $styles['outerHeight'] = $styles['height'] + $styles['margin'][2]
                + $styles['margin'][0];
        } else {
            $styles['outerHeight'] = $this->styles['height'] + $styles['margin'][0] + $styles['margin'][2];
            $styles['innerHeight'] = $this->styles['height'] - $styles['padding'][0] - $styles['padding'][2];
        }
        $styles['radius'] = NodeHelper::padding($styles, 'radius');
        return $styles;
    }

    public function draw(Image $box = null) {
        if (empty($box)) {
            $this->refresh([
                'color' => '#000',
                'font-size' => 16
            ]);
            $box = new Image();
            $box->instance()->create(new Box($this->computed['outerWidth'], $this->computed['outerHeight']));
            if (!isset($this->computed['background'])) {
                $this->computed['background'] = '#fff';
            }
        }
        if (isset($this->computed['background'])) {
            $x = $this->computed['x'];
            $y = $this->computed['y'];
            $width = $this->computed['width'];
            $height = $this->computed['height'];
            $radius = $this->computed['radius'];
            if ($this->computed['background'] instanceof ImgNode) {
                $this->drawBackgroundImage($box, $this->computed['background'], $x, $y, $width, $height, $radius);
            } else {
                $this->drawFill($box, $this->computed['background'], $x, $y, $width, $height, $radius);
            }
        }
        foreach ($this->children as $node) {
            $node->draw($box);
        }
        return $box;
    }

    protected function drawFill(Image $box, $color, $x, $y, $width, $height, $radius) {
        if ($this->isEmpty($radius)) {
            $box->instance()->fill($color);
            return;
        }
        $img = ImageManager::create()
            ->create(new Box($width, $height), $color);
        $bg = $img->converterFromColor($img->converterToColor($color));
        $tempColor = [255 - $bg[0], 255 - $bg[1], 255 - $bg[2], 1];
        $this->setColorOutBox($img, $tempColor, $radius);
        $img->transparent($tempColor);
        $box->instance()->paste($img, new Point($x, $y));
        unset($img);
    }

    protected function drawBackgroundImage(Image $box, ImgNode $node, $x, $y, $width, $height, $radius) {
        if ($this->isEmpty($radius)) {
            $node->refresh($this->computed);
            $node->draw($box);
            return;
        }
        $image = clone $node->getImage();
        $image->scale(new Box($width, $height));
        $tempColor = [0, 0, 0, 1];
        $this->setColorOutBox($image, $tempColor, $radius);
        $image->transparent($tempColor);
        $box->instance()->paste($image, new Point($x, $y));
    }

    protected function setColorOutBox(ImageAdapter $box, $color, $radius) {
        $width = $box->getWidth();
        $height = $box->getHeight();
        $each = function ($radius, $cb) {
            if ($radius < 1) {
                return;
            }
            for ($i = 0; $i < $radius; $i ++) {
                for ($j = 0; $j < $radius; $j ++) {
                    if (pow($radius - $i, 2) + pow($radius - $j, 2)
                        > pow($radius, 2)) {
                        $cb($i, $j);
                    } else {
                        break;
                    }
                }
            }
        };
        $each($radius[0], function ($i, $j) use ($box, $color) {
            $box->dot(new Point($i, $j), $color);
        });
        $each($radius[1], function ($i, $j) use ($width, $box, $color) {
            $box->dot(new Point($width - $i, $j), $color);
        });
        $each($radius[2], function ($i, $j) use ($width, $height, $box, $color) {
            $box->dot(new Point($width - $i, $height - $j), $color);
        });
        $each($radius[3], function ($i, $j) use ($width, $height, $box, $color) {
            $box->dot(new Point($i, $height - $j), $color);
        });
    }

    protected function isBoxInner($x, $y, $width, $height, $radius) {
        if ($radius[0] > 0) {
            if ($x < $radius[0] && $y < $radius[0]) {
                return pow($radius[0] - $x, 2) + pow($radius[0] - $y, 2)
                    < pow($radius[0], 2);
            }
        }
        if ($radius[1] > 0) {
            if ($x > $width - $radius[1] && $y < $radius[1]) {
                return pow($x - $width + $radius[1], 2) + pow($radius[1] - $y, 2)
                    < pow($radius[1], 2);
            }
        }
        if ($radius[2] > 0) {
            if ($x > $width - $radius[2] && $y > $height - $radius[2]) {
                return pow($x - $width + $radius[2], 2) + pow($y - $height + $radius[2], 2)
                    < pow($radius[2], 2);
            }
        }
        if ($radius[3] > 0) {
            if ($x < $radius[3] && $y > $height - $radius[3]) {
                return pow($radius[3] - $x, 2) + pow($y - $height + $radius[3], 2)
                    < pow($radius[3], 2);
            }
        }
        return $x > 0 && $x < $width && $y > 0 && $y < $height;
    }

    protected function isEmpty($data) {
        if (empty($data)) {
            return true;
        }
        foreach ($data as $val) {
            if (!empty($val)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param array $properties
     * @return BoxNode
     */
    public static function create(array $properties = []) {
        return (new static())->setStyles($properties);
    }

    public static function parse($content) {
        $lines = explode(PHP_EOL, ltrim($content));
        // 修复linux换行符
        if (preg_match('/^\[(.+)\]$/', trim($lines[0]), $match)) {
            $box = static::create(static::parseProperties($match[1]));
            array_shift($lines);
        } else {
            $box = new static();
        }
        foreach ($lines as $line) {
            $box->append(static::parseNode($line));
        }
        return $box;
    }

    protected static function parseNode($content) {
        if (!preg_match('/^\[(.+)\](.*)$/', $content, $match)) {
            return TextNode::create($content);
        }
        $properties = static::parseProperties($match[1]);
        if (isset($properties['img'])) {
            return ImgNode::create($match[2], $properties);
        }
        return TextNode::create($match[2], $properties);
    }

    protected static function parseProperties($content) {
        $properties = [];
        foreach (explode(' ', $content) as $line) {
            $args = explode('=', $line, 2);
            if (count($args) === 1) {
                $args[1] = true;
                if (strpos($args[0], '!') === 0) {
                    $args[1] = false;
                    $args[0] = substr($args[0], 1);
                }

            }
            $properties[$args[0]] = static::parseVal($args[1]);
        }
        return $properties;
    }

    protected static function parseVal($val) {
        if (is_numeric($val)) {
            return $val;
        }
        if ($val === 'true') {
            return true;
        }
        if ($val === 'false') {
            return $val;
        }
        return $val;
    }
}