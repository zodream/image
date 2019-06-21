<?php
namespace Zodream\Image\Node;

use Zodream\Helpers\Str;
use Zodream\Image\Canvas;

class BoxNode {
    /**
     * 设置的属性
     * @var array
     */
    protected $properties = [];

    /**
     * @var ImgNode[]
     */
    protected $children = [];

    /**
     * 生成的属性
     * @var array
     */
    protected $styles = [];


    /**
     * @param array $properties
     * @return BoxNode
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

    public function append(...$nodes) {
        $this->children = array_merge($this->children, $nodes);
        $this->styles = [];
        return $this;
    }

    public function refresh(array $properties = []) {
        if (!empty($this->styles) && empty($properties)) {
            return;
        }
        $properties = array_merge($this->properties, $properties);
        $properties['padding'] = NodeHelper::padding($properties);
        $properties['margin'] = NodeHelper::padding($properties, 'margin');
        $properties['innerWidth'] = $properties['width'] - $properties['padding'][1] - $properties['padding'][3];
        $properties['outerWidth'] = $properties['width'] + $properties['margin'][1] - $properties['margin'][3];
        $properties['x'] = $properties['margin'][1] + $properties['padding'][1];
        $properties['y'] = $properties['margin'][0] + $properties['padding'][0];
        foreach ($this->children as $node) {
            $properties['y'] += $node->refresh($properties);
        }
        if (!isset($properties['height'])) {
            $properties['outerHeight'] = $properties['y'] + $properties['margin'][2] + $properties['padding'][2];
            $properties['height'] = $properties['y'] + $properties['padding'][2] - $properties['margin'][0];
            $properties['innerHeight'] = $properties['y'] - $properties['margin'][0] - $properties['padding'][0];
        } else {
            $properties['outerHeight'] = $properties['height'] + $properties['margin'][0] + $properties['margin'][2];
            $properties['innerHeight'] = $properties['height'] - $properties['padding'][0] - $properties['padding'][2];
        }
        $this->styles = array_merge($properties, [
            'x' => 0,
            'y' => 0
        ]);
    }

    public function draw() {
        $this->refresh();
        $box = new Canvas();
        $box->create($this->styles['outerWidth'], $this->styles['outerHeight']);
        $box->setBackground($this->styles['background']);
        foreach ($this->children as $node) {
            $node->draw($box);
        }
        return $box;
    }

    /**
     * @param array $properties
     * @return BoxNode
     */
    public static function create(array $properties) {
        return (new static())->setProperties($properties);
    }

    public static function parse($content) {
        $lines = explode(PHP_EOL, ltrim($content));
        if (preg_match('/^\[(.+)\]$/', $lines[0], $match)) {
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
            }
            $properties[$args[0]] = $args[1];
        }
        return $properties;
    }
}