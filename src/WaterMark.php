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
	 * @param $direction
	 * @param int $width
	 * @param int $height
	 * @return array
	 */
	public function getPointByDirection($direction, $width = 0, $height = 0) {
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
		switch ($direction) {
			case self::Center:
				return array(
					($this->instance()->getWidth() - $width) / 2,
					($this->instance()->getHeight() - $height) / 2,
					$width,
					$height
				);
			case self::Left:
				return array(
					0,
					($this->instance()->getHeight() - $height) / 2,
					$width,
					$height
				);
			case self::Top:
				return array(
					($this->instance()->getWidth() - $width) / 2,
					0,
					$width,
					$height
				);
			case self::RightTop:
				return array(
					$this->instance()->getWidth() - $width,
					0,
					$width,
					$height
				);
			case self::Right:
				return array(
					$this->instance()->getWidth() - $width,
					($this->instance()->getHeight() - $height) / 2,
					$width,
					$height
				);
			case self::RightBottom:
				return array(
					$this->instance()->getWidth() - $width,
					$this->instance()->getHeight() - $height,
					$width,
					$height
				);
			case self::Bottom:
				return array(
					($this->instance()->getWidth() - $width) / 2,
					$this->instance()->getHeight() - $height,
					$width,
					$height
				);
			case self::LeftBottom:
				return array(
					0,
					$this->instance()->getHeight() - $height,
					$width,
					$height
				);

			case self::LeftTop:
			default:
				return array(
					0,
					0,
					$width,
					$height
				);
		}
	}

    /**
     * 根据九宫格添加文字
     * @param $text
     * @param int $direction
     * @param int $fontSize
     * @param string $color
     * @param int $fontFamily
     * @return static
     */
	public function addTextByDirection($text, $direction = self::Top, $fontSize = 16, $color = '#000', $fontFamily = 5) {
		list($x, $y) = $this->getPointByDirection($direction);
		$this->instance()->text($text, new Font($fontFamily, $fontSize, $color), new Point($x, $y));
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
     * @throws \Zodream\Infrastructure\Error\Exception
     */
	public function addText($text, $x = 0, $y = 0, $fontSize = 16, $color = '#000', $fontFamily = 5, $angle = 0) {
		$this->instance()->text($text, new Font($fontFamily, $fontSize, $color), new Point($x, $y));
	    return $this;
	}

    /**
     * 根据九宫格添加图片
     * @param $image
     * @param int $direction
     * @param int $opacity
     * @return static
     */
	public function addImageByDirection($image, $direction = self::Top, $opacity = 50) {
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
	public function addImage($image, $x = 0, $y = 0, $opacity = 50) {
        if ($image instanceof Image) {
            $image = $image->instance();
        } elseif (!$image instanceof ImageAdapter) {
            $image = ImageManager::create()->loadResource($image);
        }
        $this->instance()->paste($image, new Point($x, $y), $opacity);
        return $this;
	}
}