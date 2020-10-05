<?php
namespace Zodream\Image\Node;

use Zodream\Helpers\Str;
use Zodream\Image\Canvas;
use Zodream\Image\Image;

class BoxNode extends BaseNode {

    /**
     * @var BaseNode[]
     */
    protected $children = [];

    public function append(...$nodes) {
        $this->children = array_merge($this->children, $nodes);
        $this->computed = [];
        return $this;
    }

    public function refresh(array $properties = []) {
        $styles = NodeHelper::mergeStyle($this->styles, $properties);
        $parentStyles = array_merge($properties, $styles, [
            'brotherRight' => $styles['x'],
            'brotherTop' => $styles['y'],
        ]);
        foreach ($this->children as $node) {
            $node->refresh($parentStyles);
            $parentStyles['y'] += $node->placeholderHeight();
            $parentStyles['brotherRight'] = $node->getRight();
            $parentStyles['brotherTop'] = $node->getTop();
        }
        if (!isset($this->styles['height'])) {
            $styles['outerHeight'] = $parentStyles['y'] + $styles['margin'][2]
                + $styles['padding'][2];
            $styles['height'] = $parentStyles + $styles['padding'][2] - $styles['margin'][0];
            $styles['innerHeight'] = $parentStyles - $styles['margin'][0] - $styles['padding'][0];
        } else {
            $styles['outerHeight'] = $this->styles['height'] + $styles['margin'][0] + $styles['margin'][2];
            $styles['innerHeight'] = $this->styles['height'] - $styles['padding'][0] - $styles['padding'][2];
        }
        $this->computed = $styles;
    }

    public function draw(Image $box = null) {
        if (empty($box)) {
            $this->refresh([
                'color' => '#000',
                'font-size' => 16
            ]);
            $box = new Canvas();
            $box->create($this->computed['outerWidth'], $this->computed['outerHeight']);
            if (!isset($this->computed['background'])) {
                $this->computed['background'] = '#fff';
            }
        }
        if (isset($this->computed['background'])) {
            if ($this->computed['background'] instanceof ImgNode) {
                $this->computed['background']->refresh($this->computed);
                $this->computed['background']->draw($box);
            } else {
                $box->setBackground($this->computed['background']);
            }
        }
        foreach ($this->children as $node) {
            $node->draw($box);
        }
        return $box;
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