<?php
namespace Zodream\Image;

class SlideCaptcha extends Image {

    /**
     * @var integer[]
     */
    protected $point;

    protected $alpha = .5;

    /**
     * @var Image
     */
    protected $shapeImage;

    /**
     * @var Image
     */
    protected $slideImage;

    public function setAlpha($alpha) {
        $this->alpha = $alpha;
        return $this;
    }

    public function setShape($shape) {
        $this->shapeImage = $shape instanceof Image ? $shape : new Image($shape);
        return $this;
    }

    public function setPoint($x, $y) {
        $this->point = [$x, $y];
        return $this;
    }

    /**
     * @return integer[]
     */
    public function getPoint() {
        return $this->point;
    }

    /**
     * @return Image
     */
    public function getSlideImage() {
        return $this->slideImage;
    }

    public function generate() {
        $this->drawBox();
    }

    public function drawBox() {
        $with = $this->shapeImage->getWidth();
        $height = $this->shapeImage->getHeight();
        if (empty($this->point)) {
            $this->point = [
                rand($with, $this->width - $with),
                rand(0, $this->height - $height)
            ];
        }
        $this->slideImage = ImageStatic::canvas($with, $height, 'png');
        for ($i = 0; $i < $with; $i ++) {
            for ($j = 0; $j < $height; $j ++) {
                if (!$this->isValidBound($i, $j)) {
                    continue;
                }
                $real_x = $i + $this->point[0];
                $real_y = $j + $this->point[1];
                $color = $this->getColor($real_x, $real_y);
                $this->slideImage->setColor($i, $j, $color);
                list($r, $g, $b) = $this->getRgbByColor($color);
                $this->setColor($real_x, $real_y, [
                    floor($r * $this->alpha),
                    floor($g * $this->alpha),
                    floor($b * $this->alpha),
                ]);
            }
        }
        $this->slideImage->setTransparent(0, 0, 0);
    }

    /**
     * 是否是有效的区域
     * @param $x
     * @param $y
     * @return true
     */
    public function isValidBound($x, $y) {
        list($r, $g, $b) = $this->shapeImage->getRGB($x, $y);
        return $r < 240 || $g < 240 || $b < 240;
    }

    /**
     * 按指定数值打乱排序重新生成图片
     * @param array ...$args
     * @param int $rows 多少层 默认2层
     * @return array [Image, point[], [width, height]]
     */
    public function sortBy($args, $rows = 2) {
        if (!is_array($args)) {
            $args = func_get_args();
            $rows = 2;
        }
        $min = min(...$args);
        $max = max(...$args);
        $image = ImageStatic::canvas($this->width, $this->height);
        $length = $max - $min + 1;
        $count = ceil($length / $rows);
        $width = $this->width / $count;
        $height = $this->height / $rows;
        $points = [];
        foreach ($args as $i => $arg) {
            $arg = $arg - $min;
            $x = ($arg % $count)  * $width;
            $y = floor($arg / $count) * $height;
            $srcX = ($i % $count) * $width;
            $srcY = floor($i / $count) * $height;
            // 计算显示是图片唯一
            $points[] = [- $x, -$y];
            $image->copyFromWithReSampling($this, $srcX, $srcY, $x, $y, $width, $height, $width, $height);

        }
        return [$image, $points, [$width, $height]];
    }

}