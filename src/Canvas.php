<?php
namespace Zodream\Image;

use Zodream\Image\Node\Box;
use Zodream\Image\Node\Text;

class Canvas extends Image {

    public function setBackground($color) {
        return $this->fill($color);
    }

    public function addImage(Image $image, Box $box) {
        $this->copyFromWithResize($image, 0, 0, $box->x, $box->y,
            0, 0, $box->width, $box->height);
        return $this;
    }

    /**
     * 加文字
     * @param Text $text
     * @param int $angle 如果 $fontFamily 为 int，则不起作用
     * @return Canvas
     */
    public function addText(Text $text, $angle = 0) {
        $color = $this->getColorWithRGB($text->color);
        if (is_string($text->font) && is_file($text->font)) {
            imagettftext($this->image, $text->size, $angle,
                $text->x, $text->y, $color, $text->font, $text->content);
            return $this;
        }
        imagestring($this->image, intval($text->font), $text->x, $text->y,
            $text->content, $color);
        return $this;
    }

    public function show() {
        return app('response')->image($this)->send();
    }
}