<?php
namespace Zodream\Image;

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
			$width = $this->getWidth() / 3;
			$height = $this->getHeight() / 3;
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
					($this->getWidth() - $width) / 2,
					($this->getHeight() - $height) / 2,
					$width,
					$height
				);
			case self::Left:
				return array(
					0,
					($this->getHeight() - $height) / 2,
					$width,
					$height
				);
			case self::Top:
				return array(
					($this->getWidth() - $width) / 2,
					0,
					$width,
					$height
				);
			case self::RightTop:
				return array(
					$this->getWidth() - $width,
					0,
					$width,
					$height
				);
			case self::Right:
				return array(
					$this->getWidth() - $width,
					($this->getHeight() - $height) / 2,
					$width,
					$height
				);
			case self::RightBottom:
				return array(
					$this->getWidth() - $width,
					$this->getHeight() - $height,
					$width,
					$height
				);
			case self::Bottom:
				return array(
					($this->getWidth() - $width) / 2,
					$this->getHeight() - $height,
					$width,
					$height
				);
			case self::LeftBottom:
				return array(
					0,
					$this->getHeight() - $height,
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
     * @return array|bool
     */
	public function addTextByDirection($text, $direction = self::Top, $fontSize = 16, $color = '#000', $fontFamily = 5) {
		list($x, $y) = $this->getPointByDirection($direction);
		return $this->addText($text, $x, $y, $fontSize, $color, $fontFamily);
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
	 * @return array|bool
	 */
	public function addText($text, $x = 0, $y = 0, $fontSize = 16, $color = '#000', $fontFamily = 5, $angle = 0) {
		$color = $this->getColorWithRGB($color);
		if (is_string($fontFamily) && is_file($fontFamily)) {
			return imagettftext($this->image, $fontSize, $angle, $x, $y, $color, $fontFamily, $text);
		}
		$fontFamily = intval($fontFamily);
		return imagestring($this->image, $fontFamily, $x, $y, $text, $color);
	}

    /**
     * 根据九宫格添加图片
     * @param $image
     * @param int $direction
     * @param int $opacity
     * @return bool
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
	 * @return bool
	 */
	public function addImage($image, $x = 0, $y = 0, $opacity = 50) {
		if (!$image instanceof Image) {
			$image = new Image($image);
		}
		if ($image->getRealType() == 'png') {
			return $this->copyFrom($image, 0, 0, $x, $y);
		}
		return $this->copyAndMergeFrom($image, $x, $y, $opacity);
	}
}