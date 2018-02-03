<?php
namespace Zodream\Image\Text;


class TextLine extends TextBlock {



    public function __construct($content) {
        if (empty($content)) {
            return;
        }
        if (!preg_match('/^\[([^\[\]]*)\]([\<\>]?)/', $content, $match)) {
            $this->setBlocks(TextBlock::parse($content));
            return;
        }
        if (!empty($match[2])) {
            $this->setBlocks(TextBlock::parse($content));
            return;
        }
        $this->parseProperty($match[1])
            ->setBlocks(TextBlock::parse(substr($content, strlen($match[0]))));
    }



    public static function parse($content) {
        $lines = [];
        foreach (explode(PHP_EOL, $content) as $item) {
            $lines[] = new static(trim($item, "\r"));
        }
        return $lines;
    }
}