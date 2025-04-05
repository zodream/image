<?php
declare(strict_types=1);
namespace Zodream\Image;

use Zodream\Image\Adapters\Gd;
use Zodream\Image\Adapters\Gmagick;
use Zodream\Image\Adapters\ImageAdapter;
use Zodream\Image\Adapters\Imagick;
use Zodream\Image\Base\Box;
use Zodream\Image\Base\BoxInterface;
use Zodream\Image\Base\Font;
use Zodream\Image\Base\FontInterface;
use Zodream\Image\Base\Point;
use Zodream\Image\Base\PointInterface;

final class ImageManager {

    private static array $map = [
        'gd' => Gd::class,
        'gd2' => Gd::class,
        'imagick' => Imagick::class,
        'gmagick' => Gmagick::class,
    ];

    /**
     * @param string $driver
     * @return ImageAdapter
     */
    public static function create(string $driver = ''): ImageAdapter {
        if (empty($driver)) {
            $driver = !function_exists('config') ? 'gd' : config('image.driver', 'gd');
        }
        if (isset(self::$map[$driver])) {
            $driver = self::$map[$driver];
        }
        return new $driver();
    }

    /**
     * 创建字体
     * @param mixed $file
     * @param int $size
     * @param mixed $color
     * @return FontInterface
     */
    public static function createFont(mixed $file, int $size = 16, mixed $color = '#000') : FontInterface
    {
        return new Font(is_int($file) ? $file : (string)$file, $size, $color);
    }

    /**
     * 创建尺寸
     * @param float|int $width
     * @param float|int $height
     * @return BoxInterface
     */
    public  static function createSize(float|int  $width, float|int  $height): BoxInterface
    {
        return new Box($width, $height);
    }

    /**
     * 创建点
     * @param float|int $x
     * @param float|int $y
     * @return PointInterface
     */
    public  static function createPoint(float|int  $x, float|int  $y): PointInterface
    {
        return new Point((int)$x, (int)$y);
    }
}