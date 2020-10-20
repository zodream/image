<?php
namespace Zodream\Image;

use Zodream\Image\Adapters\ImageAdapter;
use Zodream\Image\Base\Box;
use Zodream\Image\Base\Point;

class SlideCaptcha extends Image {

    /**
     * @var integer[]
     */
    protected $point;

    protected $alpha = .5;

    /**
     * @var ImageAdapter
     */
    protected $shapeImage;

    /**
     * @var ImageAdapter
     */
    protected $slideImage;

    public function setAlpha($alpha) {
        $this->alpha = $alpha;
        return $this;
    }

    public function setShape($shape) {
        if ($shape instanceof Image) {
            $shape = $shape->instance();
        } elseif (!$shape instanceof ImageAdapter) {
            $shape = ImageManager::create()->loadResource($shape);
        }
        $this->shapeImage = $shape;
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
     * @return ImageAdapter
     */
    public function getSlideImage() {
        return $this->slideImage;
    }

    public function generate() {
        $this->drawBox();
    }

    public function drawBox() {
        $width = $this->shapeImage->getWidth();
        $height = $this->shapeImage->getHeight();
        if (empty($this->point)) {
            $this->point = [
                rand($width, $this->instance()->getWidth() - $width),
                rand(0, $this->instance()->getHeight() - $height)
            ];
        }
        $this->slideImage = ImageManager::create()->create(new Box($width, $height))
            ->setRealType('png');
        for ($i = 0; $i < $width; $i ++) {
            for ($j = 0; $j < $height; $j ++) {
                $current = $this->isValidBound($i, $j);
                if (!$current) {
                    continue;
                }
                $real_x = $i + $this->point[0];
                $real_y = $j + $this->point[1];
                $color = $this->instance()->getColorAt(new Point($real_x, $real_y));
                $this->slideImage->dot(new Point($i, $j), $color);
                list($r, $g, $b) = $this->instance()->converterFromColor($color);
                $this->instance()->dot(new Point($real_x, $real_y), [
                    floor($r * $this->alpha),
                    floor($g * $this->alpha),
                    floor($b * $this->alpha),
                ]);
            }
        }
        $this->slideImage->transparent([0, 0, 0]);
    }

    /**
     * 是否是有效的区域
     * @param $x
     * @param $y
     * @return true
     */
    public function isValidBound($x, $y) {
        list($r, $g, $b) = $this->shapeImage->converterFromColor(
            $this->shapeImage->getColorAt(new Point($x, $y)));
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
        $image = new Image();
        $image->instance()->create($this->instance()->getSize());
        $length = $max - $min + 1;
        $count = ceil($length / $rows);
        $width = $this->instance()->getWidth() / $count;
        $height = $this->instance()->getHeight() / $rows;
        $points = [];
        foreach ($args as $i => $arg) {
            $arg = $arg - $min;
            $x = ($arg % $count)  * $width;
            $y = floor($arg / $count) * $height;
            $srcX = ($i % $count) * $width;
            $srcY = floor($i / $count) * $height;
            // 计算显示是图片唯一
            $points[] = [- $x, -$y];
            $image->instance()->pastePart($this->instance(), new Point($srcX, $srcY),
                new Box($width, $height), new Point($x, $y));

        }
        return [$image, $points, [$width, $height]];
    }

}