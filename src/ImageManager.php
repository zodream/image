<?php
declare(strict_types=1);
namespace Zodream\Image;

use Zodream\Image\Adapters\AbstractImage;
use Zodream\Image\Adapters\Gd;
use Zodream\Image\Adapters\Gmagick;
use Zodream\Image\Adapters\ImageAdapter;
use Zodream\Image\Adapters\Imagick;

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
}