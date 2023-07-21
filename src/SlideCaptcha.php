<?php
declare(strict_types=1);
namespace Zodream\Image;

use Zodream\Image\Adapters\ImageAdapter;
use Zodream\Image\Base\Box;
use Zodream\Image\Base\Point;

class SlideCaptcha extends Image implements ICaptcha {

    protected array $configs = [
        'alpha' => .5,
        'slices' => 8, // 切片数量
        'width' => 300,
        'height' => 130,
        'shapeWidth' => 20,
        'shapeHeight' => 20
    ];

    /**
     * @var integer[]
     */
    protected array $point = [];

    /**
     * @var ImageAdapter
     */
    protected ?ImageAdapter $shapeImage = null;

    /**
     * @var ImageAdapter
     */
    protected ?ImageAdapter $slideImage = null;

    public function setConfigs(array $configs): void {
        $this->configs = array_merge($this->configs, $configs);
    }

    public function isOnlyImage(): bool {
        return false;
    }

    public function setShape(mixed $shape) {
        if ($shape instanceof Image) {
            $shape = $shape->instance();
        } elseif (!$shape instanceof ImageAdapter) {
            $shape = ImageManager::create()->loadResource($shape);
        }
        if ($shape->getWidth() > $this->configs['width'] || $shape->getHeight() > $this->configs['height']) {
            $shape->scale(new Box($this->configs['shapeWidth'], $this->configs['shapeHeight']));
        }
        $this->shapeImage = $shape;
        return $this;
    }

    public function setPoint(int $x, int $y) {
        $this->point = [$x, $y];
        return $this;
    }

    /**
     * @return integer[]
     */
    public function getPoint(): array {
        return $this->point;
    }

    /**
     * @return ImageAdapter
     */
    public function getSlideImage(): ImageAdapter {
        return $this->slideImage;
    }

    public function generate(): mixed {
        $this->instance()->scale(new Box($this->configs['width'],
            $this->configs['height']));
        $this->drawBox();
        return $this->point;
    }

    public function drawBox(): void {
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
        $alpha = floatval($this->configs['alpha']);
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
                    floor($r * $alpha),
                    floor($g * $alpha),
                    floor($b * $alpha),
                ]);
            }
        }
        $this->slideImage->transparent([0, 0, 0]);
    }

    /**
     * 是否是有效的区域
     * @param int $x
     * @param int $y
     * @return true
     */
    public function isValidBound(int $x, int $y): bool {
        list($r, $g, $b) = $this->shapeImage->converterFromColor(
            $this->shapeImage->getColorAt(new Point($x, $y)));
        return $r < 240 || $g < 240 || $b < 240;
    }

    /**
     * 按指定数值打乱排序重新生成图片
     * @param int[] $args
     * @param int $rows 多少层 默认2层
     * @return array [Image, point[], [width, height]]
     */
    public function sortBy(array $args, int $rows = 2): array {
        return ImageHelper::sortBy($this->instance(), $args, $rows);
    }

    public function verify(mixed $value, mixed $source): bool {
        $x = ImageHelper::x($value);
        $srcX = ImageHelper::x($source);
        return abs($x - $srcX) < 5;
    }

    public function toArray(): array {
        $args = range(0, intval($this->configs['slices']) - 1);
        shuffle($args);
        list($bg, $points, $size) = $this->sortBy($args);
        return [
            'image' => $bg->toBase64(),
            'width' => $this->instance()->getWidth(),
            'height' => $this->instance()->getHeight(),
            'imageItems' => array_map(function ($item) use ($size) {
                return [
                    'x' => $item[0],
                    'y' => $item[1],
                    'width' => $size[0],
                    'height' => $size[1]
                ];
            }, $points),
            'control' => $this->slideImage->toBase64(),
            'controlWidth' => $this->slideImage->getWidth(),
            'controlHeight' => $this->slideImage->getHeight(),
            'controlY' => $this->point[1]
        ];
    }

}