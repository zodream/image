<?php
namespace Zodream\Image\Node;


class Node {

    /**
     * @var array
     */
    public $children = [];

    public function text($content) {
        $text = new Text($content, 0, 0, '#000', 5, 16);
        $this->children[] = $text;
        return $text;
    }

    public function image() {

    }
}