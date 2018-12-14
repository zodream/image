<?php
namespace Zodream\Image;

class ImageStatic {

    /**
     * 加载图片文件
     * @param $data
     * @return Image
     */
    public static function make($data) {
        return new Image($data);
    }

    /**
     * 创建
     * @param $width
     * @param $height
     * @param string $type
     * @return Image
     */
    public static function canvas($width, $height, $type = 'jpeg') {
        return (new Image())->create($width, $height, $type);
    }
}