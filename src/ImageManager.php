<?php
namespace Zodream\Image;

use Zodream\Image\Adapters\Gd;
use Zodream\Image\Adapters\Gmagick;
use Zodream\Image\Adapters\ImageAdapter;
use Zodream\Image\Adapters\Imagick;

final class ImageManager {

    private static $map = [
        'gd' => Gd::class,
        'gd2' => Gd::class,
        'imagick' => Imagick::class,
        'gmagick' => Gmagick::class,
    ];

    /**
     * @param string $driver
     * @return ImageAdapter
     */
    public static function create(string $driver = '') {
        if (empty($driver)) {
            $driver = config('image.driver', 'gd');
        }
        if (isset(self::$map[$driver])) {
            $driver = self::$map[$driver];
        }
        return new $driver();
    }
}