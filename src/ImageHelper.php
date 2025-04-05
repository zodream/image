<?php
declare(strict_types=1);
namespace Zodream\Image;

use Zodream\Image\Adapters\ImageAdapter;
use Zodream\Image\Base\Box;
use Zodream\Image\Base\Point;
use Zodream\Image\Base\PointInterface;

class ImageHelper {

    /**
     * 按指定数值打乱排序重新生成图片
     * @param int[] ...$args
     * @param int $rows 多少层 默认2层
     * @return array [Image, point[], [width, height]]
     */
    public static function sortBy(ImageAdapter $source, array $args, int $rows = 2): array {
        $min = min(...$args);
        $max = max(...$args);
        $image = new Image();
        $image->instance()->create($source->getSize());
        $length = $max - $min + 1;
        $count = ceil($length / $rows);
        $width = $source->getWidth() / $count;
        $height = $source->getHeight() / $rows;
        $points = [];
        foreach ($args as $i => $arg) {
            $arg = $arg - $min;
            $x = ($arg % $count)  * $width;
            $y = floor($arg / $count) * $height;
            $srcX = ($i % $count) * $width;
            $srcY = floor($i / $count) * $height;
            // 计算显示是图片唯一
            $points[] = [- $x, -$y];
            $image->instance()->pastePart($source, new Point((int)$srcX, (int)$srcY),
                new Box($width, $height), new Point((int)$x, (int)$y));

        }
        return [$image, $points, [$width, $height]];
    }

    /**
     * 计算两点之间的直线距离
     * @param int|float $fromX
     * @param int|float $fromY
     * @param int|float $toX
     * @param int|float $toY
     * @return float
     */
    public static function distance(int|float $fromX, int|float $fromY,
                                    int|float $toX, int|float $toY): float {
        return sqrt(pow($fromX - $toX, 2) + pow($fromY - $toY, 2));
    }

    /**
     * 获取坐标 x 值
     * @param mixed $point
     * @return float
     */
    public static function x(mixed $point): float {
        if ($point instanceof PointInterface) {
            return $point->getX();
        }
        if (!is_array($point)) {
            return floatval($point);
        }
        if (isset($point['x'])) {
            return floatval($point['x']);
        }
        return isset($point[0]) ? floatval($point[0]) : 0;
    }

    /**
     * 获取坐标 y 值
     * @param mixed $point
     * @return float
     */
    public static function y(mixed $point): float {
        if ($point instanceof PointInterface) {
            return $point->getY();
        }
        if (!is_array($point)) {
            return floatval($point);
        }
        if (isset($point['y'])) {
            return floatval($point['y']);
        }
        return isset($point[1]) ? floatval($point[1]) : 0;
    }

    /**
     * 判断点在区域内
     * @param mixed $point
     * @param int|float $x
     * @param int|float $y
     * @param int|float $width
     * @param int|float $height
     * @return bool
     */
    public static function inBound(mixed $point, int|float $x, int|float $y,
                                   int|float $width, int|float $height): bool {
        $pX = static::x($point);
        $pY = static::y($point);
        return $pX >= $x && $pX <= $x + $width && $pY >= $y && $pY <= $y + $height;
    }

    /**
     * 生成成随机数
     * @param int $min
     * @param int $max
     * @param int $count
     * @return int[]
     */
    public static function randomInt(int $min, int $max, int $count = 2): array {
        if ($max - $min <= $count) {
            throw new \Exception('range is error');
        }
        $items = [];
        while ($count > 0) {
            $i = random_int($min, $max);
            if (in_array($i, $items)) {
                continue;
            }
            $items[] = $i;
            $count --;
        }
        return $items;
    }
}