<?php
namespace Zodream\Image;
use Zodream\Image\Base\Box;

/**
 * 缩略图
 * @author zx648
 *
 */
class ThumbImage extends Image {
	/**
	 * 缩率图
	 * @param string $output 缩率之后存储的图片
	 * @param int $thumbWidth 缩率图宽度
	 * @param int $thumbHeight 缩率图高度
	 * @param bool|int $auto 那种方式进行缩略处理
	 * @return string
	 */
	public function thumb($output, $thumbWidth = 0, $thumbHeight = 0, $auto = true){
		$width = $this->instance()->getWidth();
		$height = $this->instance()->getHeight();
		if ($thumbWidth <= 0) {
			$thumbWidth = $auto ? ($thumbHeight / $height * $width) : $width;
		} elseif ($thumbHeight <= 0) {
			$thumbHeight = $auto ? ($thumbWidth / $width * $height) : $height;
		} elseif($auto) {
			$rate = min($height / $thumbHeight, $width / $thumbWidth);
            $thumbWidth *=  $rate;
            $thumbHeight *= $rate;
		}
        $thumb = clone $this->instance();
		$thumb->scale(new Box($width, $height));
		$thumb->saveAs($output);
		return $output;
	}
}