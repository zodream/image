<?php
namespace Zodream\Image;

use Zodream\Image\Adapters\ImageAdapter;
use Zodream\Image\Base\Font;
use Zodream\Image\Base\Point;

/**
 * 加水印
 * @author zx648
 *
 */
class WaterMark extends Image {

	const Top = 1;
	const RightTop = 2;
	const Right = 5;
	const RightBottom = 8;
	const Bottom = 7;
	const LeftBottom = 6;
	const Left = 3;
	const LeftTop = 0;
	const Center = 4;

    /**
     * 根据九宫格获取坐标
     * @param int $direction
     * @param int $width 水印的宽
     * @param int $height 水印的高
     * @param int $padding 边距
     * @return array
     */
	public function getPointByDirection(int $direction, int $width = 0, int $height = 0, int $padding = 0): array {
		if (empty($width) || empty($height)) {
			$width = $this->instance()->getWidth() / 3;
			$height = $this->instance()->getHeight() / 3;
			return array(
				$direction % 3 * $width,
				$direction / 3 * $height,
				$width,
				$height
			);
		}
        return match ($direction) {
            self::Center => array(
                ($this->instance()->getWidth() - $width) / 2,
                ($this->instance()->getHeight() - $height) / 2,
                $width,
                $height
            ),
            self::Left => array(
                $padding,
                ($this->instance()->getHeight() - $height) / 2,
                $width,
                $height
            ),
            self::Top => array(
                ($this->instance()->getWidth() - $width) / 2,
                $padding,
                $width,
                $height
            ),
            self::RightTop => array(
                $this->instance()->getWidth() - $width - $padding,
                $padding,
                $width,
                $height
            ),
            self::Right => array(
                $this->instance()->getWidth() - $width - $padding,
                ($this->instance()->getHeight() - $height) / 2,
                $width,
                $height
            ),
            self::RightBottom => array(
                $this->instance()->getWidth() - $width - $padding,
                $this->instance()->getHeight() - $height - $padding,
                $width,
                $height
            ),
            self::Bottom => array(
                ($this->instance()->getWidth() - $width) / 2,
                $this->instance()->getHeight() - $height - $padding,
                $width,
                $height
            ),
            self::LeftBottom => array(
                $padding,
                $this->instance()->getHeight() - $height - $padding,
                $width,
                $height
            ),
            default => array(
                $padding,
                $padding,
                $width,
                $height
            ),
        };
	}

    /**
     * 根据九宫格添加文字
     * @param string $text
     * @param int $direction
     * @param int $fontSize
     * @param string $color
     * @param string|int $fontFamily
     * @return static
     */
	public function addTextByDirection(string $text, int $direction = self::Top,
                                       int $fontSize = 16, string $color = '#000', string|int $fontFamily = 5) {
		$font = new Font($fontFamily, $fontSize, $color);
        $textBox = $this->instance()->fontSize($text, $font);
        list($x, $y) = $this->getPointByDirection($direction, $textBox->getWidth(), $textBox->getHeight());
		$this->instance()->text($text, $font, new Point($x, $y));
        return $this;
	}

    /**
     * 加文字
     * @param string $text
     * @param int $x
     * @param int $y
     * @param int $fontSize
     * @param string $color
     * @param int|string $fontFamily
     * @param int $angle 如果 $fontFamily 为 int，则不起作用
     * @return static
     */
	public function addText(string $text, int $x = 0, int $y = 0,
                            int $fontSize = 16, string $color = '#000', string|int $fontFamily = 5,
                            int $angle = 0) {
		$this->instance()->text($text, new Font($fontFamily, $fontSize, $color), new Point($x, $y), $angle);
	    return $this;
	}

    /**
     * 根据九宫格添加图片
     * @param $image
     * @param int $direction
     * @param int $opacity
     * @return static
     */
	public function addImageByDirection($image, int $direction = self::Top, int $opacity = 50) {
		list($x, $y) = $this->getPointByDirection($direction);
		return $this->addImage($image, $x, $y, $opacity);
	}

	/**
	 * 加水印图片
	 * @param string|Image $image
	 * @param int $x
	 * @param int $y
	 * @param int $opacity 透明度，对png图片不起作用
	 * @return static
	 */
	public function addImage($image, int $x = 0, int $y = 0, int $opacity = 50) {
        if ($image instanceof Image) {
            $image = $image->instance();
        } elseif (!$image instanceof ImageAdapter) {
            $image = ImageManager::create()->loadResource($image);
        }
        $this->instance()->paste($image, new Point($x, $y), $opacity);
        return $this;
	}
}