<?php
namespace Zodream\Image;

use Zodream\Disk\File;

class Image {
	
	protected $allowTypes = array(
		'jpeg' => array(
				'jpg',
				'jpeg',
				'jpe',
				'jpc',
				'jpeg2000',
				'jp2',
				'jb2'
		),
		'webp' => 'webp',
		'png' => 'png',
		'gif' => 'gif',
		'wbmp' => 'wbmp',
		'xbm' => 'xbm',
		'gd' => 'gd',
		'gd2' => 'gd2'
	);
	
	protected $file;
	
	protected $width;
	
	protected $height;
	
	protected $type;
	
	protected $realType;

	/**
	 * @var resource
	 */
	public $image;
	
	public function __construct($file = null) {
		if (is_null($file)) {
			return;
		}
		if ($file instanceof File) {
		    $this->open((string)$file);
		    return;
        }
		if (is_string($file) && is_file($file)) {
			$this->open($file);
			return;
		}
		$this->setImage($file);
	}
	
	public function open($file, $type = null) {
		if ($this->check($file)) {
			$this->file = $file;
			$imageInfo = getimagesize($file);
			$this->width = $imageInfo[0];
			$this->height = $imageInfo[1];
			$this->type = empty($type)
                ? image_type_to_extension($imageInfo[2], false)
                : $type;
			$this->setRealType($this->type);
			if (false !== $this->realType) {
				$this->image = call_user_func('imagecreatefrom'.$this->realType, $file);
			}
		}
        return $this;
	}
	
	public function create($width, $height, $type = 'jpeg') {
		$this->type = $type;
		$this->setRealType($type);
		if ($this->type == 'gif') {
			$this->image = imagecreate($width, $height);
		} else {
			$this->image = imagecreatetruecolor($width, $height);
		}
		$this->height = $height;
		$this->width = $width;
		return $this;
	}
	
	public function setImage($image) {
		if (is_string($image)) {
			$this->image = imagecreatefromstring($image);
		} elseif(is_resource($image)) {
			$this->image = $image;
		}
		$this->height = imagesy($this->image);
		$this->width = imagesx($this->image);
        return $this;
	}

	public function setEmptyImage() {
	    $this->image = null;
	    return $this;
    }
	
	public function getHeight() {
		return $this->height;
	}
	
	public function getWidth() {
		return $this->width;
	}
	
	public function getRealType() {
		return $this->realType;
	}

    /**
     * 设置真实类型
     * @param $type
     */
	public function setRealType($type) {
	    if (empty($type)) {
	        return;
        }
        foreach ($this->allowTypes as $key => $item) {
	        if ((!is_array($item) && $item == $type)
                || (is_array($item) && in_array($type, $item))) {
	            $this->realType = $type;
	            return;
            }
        }
	}
	
	public function getSize() {
		return array(
            $this->getWidth(),
            $this->getHeight()
		);
	}

	/**
	 * 获取文字转化成图片的尺寸
	 * @param string $text
	 * @param int $fontSize
	 * @param int $angle 角度
	 * @param int|string $fontFamily
	 * @return \number[] [宽, 高]
	 */
	public function getTextSize($text, $fontSize = 16, $angle = 0, $fontFamily = 5) {
		$textInfo = imagettfbbox($fontSize, $angle, $fontFamily, $text);
		return array(
				$textInfo[2] - $textInfo[6],
				$textInfo[3]-$textInfo[7]
		);
	}

    /**
     * 转化成颜色
     * @param int|string|array $color
     * @return int|false
     * @throws \Zodream\Infrastructure\Error\Exception
     */
	public function getColorWithRGB($color) {
		$color = Colors::converter(...func_get_args());
		if (is_integer($color)) {
		    return $color;
        }
		return imagecolorallocate($this->image, $color[0], $color[1], $color[2]);
	}

	/**
	 * 获取图像上的颜色
	 * @param int $x
	 * @param int $y
	 * @return int
	 */
	public function getColor($x = 0, $y = 0) {
		return imagecolorat($this->image, $x, $y);
	}

    /**
     * 从图片上直接获取rgb颜色
     * @param int $x
     * @param int $y
     * @return array
     */
	public function getRGB($x = 0, $y = 0) {
	    return $this->getRgbByColor($this->getColor($x, $y));
    }

	/**
	 * 图像上的颜色转换成[R,G,B]
	 * @param int $color
	 * @return array
	 */
	public function getRgbByColor($color) {
		$result = imagecolorsforindex($this->image, $color);
		return array(
			$result['red'],
			$result['green'],
			$result['blue']
		);
	}

    /**
     * 将某个颜色定义为透明色
     * @param $color
     * @return int
     */
	public function setTransparent($color) {
	    $color = call_user_func_array([$this, 'getColorWithRGB'], func_get_args());
	    return imagecolortransparent($this->image, $color);
    }

	/**
	 * 设置图像上一点的颜色
	 * @param $x
	 * @param $y
	 * @param int|string|array $color
	 * @return bool
	 */
	public function setColor($x, $y, $color) {
		return imagesetpixel($this->image, $x, $y, $this->getColorWithRGB($color));
	}
	
	public function getHashValue() {
		$w = 8;
		$h = 8;
		$image = new Image();
		$image->create($w, $h);
		$image->copyFromWithReSampling($this);
		$total = 0;
		$array = array();
		for( $y = 0; $y < $h; $y++) {
			for ($x = 0; $x < $w; $x++) {
				$gray = (imagecolorat($image->image, $x, $y) >> 8) & 0xFF;
				if(!isset($array[$y])) $array[$y] = array();
				$array[$y][$x] = $gray;
				$total += $gray;
			}
		}
		$image->close();
		$average = intval($total / ($w * $h * 2));
		$hash = '';
		for($y = 0; $y < $h; $y++) {
			for($x = 0; $x < $w; $x++) {
				$hash .= ($array[$y][$x] >= $average) ? '1' : '0';
			}
		}
		return $hash;
	}

	/**
	 * 复制图片的一部分
	 * @param Image $srcImage
	 * @param int $srcX
	 * @param int $srcY
	 * @param int $x
	 * @param int $y
	 * @param int $srcWidth 如果是0则取原图的宽
	 * @param int $srcHeight 如果是0则取原图的高
	 * @return bool
	 */
	public function copyFrom(Image $srcImage, $srcX = 0, $srcY = 0, $x = 0, $y = 0, $srcWidth = 0, $srcHeight = 0) {
		if (empty($srcWidth)) {
			$srcWidth = $srcImage->getWidth();
		}
		if (empty($srcHeight)) {
			$srcHeight = $srcImage->getHeight();
		}
		return imagecopy($this->image, $srcImage->image, $x, $y, $srcX, $srcY, $srcWidth, $srcHeight);
	}

	/**
	 * 从。。。复制一部分图片并融入本图片
	 * @param Image $srcImage
	 * @param int $x
	 * @param int $y
	 * @param int $opacity 透明度 0-100
	 * @param int $srcX
	 * @param int $srcY
	 * @param int $srcWidth
	 * @param int $srcHeight
	 * @return bool
	 */
	public function copyAndMergeFrom(Image $srcImage, $x = 0, $y = 0, $opacity = 50, $srcX = 0, $srcY = 0, $srcWidth = 0, $srcHeight = 0) {
		if (empty($srcWidth)) {
			$srcWidth = $srcImage->getWidth();
		}
		if (empty($srcHeight)) {
			$srcHeight = $srcImage->getHeight();
		}
		return imagecopymerge($this->image, $srcImage->image, $x, $y, $srcX, $srcY, $srcWidth, $srcHeight, $opacity);
	}

	/**
	 * 用灰度从。。。复制一部分图片并融入本图片
	 * @param Image $srcImage
	 * @param int $x
	 * @param int $y
	 * @param int $opacity 透明度 0-100
	 * @param int $srcX
	 * @param int $srcY
	 * @param int $srcWidth
	 * @param int $srcHeight
	 * @return bool
	 */
	public function copyAndMergeFromWithGray(Image $srcImage, $x = 0, $y = 0, $opacity = 50, $srcX = 0, $srcY = 0, $srcWidth = 0, $srcHeight = 0) {
		if (empty($srcWidth)) {
			$srcWidth = $srcImage->getWidth();
		}
		if (empty($srcHeight)) {
			$srcHeight = $srcImage->getHeight();
		}
		return imagecopymergegray($this->image, $srcImage->image, $x, $y, $srcX, $srcY, $srcWidth, $srcHeight, $opacity);
	}

	/**
	 * 使用重绘复制并调整图片的一部分
	 * @param Image $srcImage 本图
	 * @param int $srcX
	 * @param int $srcY
	 * @param int $x
	 * @param int $y
	 * @param int $srcWidth 如果是0则取原图的宽
	 * @param int $srcHeight 如果是0则取原图的高
	 * @param int $width 如果是0则取本图的宽
	 * @param int $height 如果是0则取本图的高
	 * @return bool
	 */
	public function copyFromWithReSampling(Image $srcImage, $srcX = 0, $srcY = 0, $x = 0, $y = 0, $srcWidth = 0, $srcHeight = 0, $width = 0, $height = 0) {
		if (empty($srcWidth)) {
			$srcWidth = $srcImage->getWidth();
		}
		if (empty($srcHeight)) {
			$srcHeight = $srcImage->getHeight();
		}
		if (empty($width)) {
			$width = $this->getWidth();
		}
		if (empty($height)) {
			$height = $this->getHeight();
		}
		return imagecopyresampled($this->image, $srcImage->image, $x, $y, $srcX, $srcY, $width, $height, $srcWidth, $srcHeight);
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
     * @throws \Zodream\Infrastructure\Error\Exception
     */
    public function text($text, $x = 0, $y = 0, $fontSize = 16, $color = '#000', $fontFamily = 5, $angle = 0) {
        $color = $this->getColorWithRGB($color);
        if (is_string($fontFamily) && is_file($fontFamily)) {
            return imagettftext($this->image, $fontSize, $angle, $x, $y, $color, $fontFamily, $text);
        }
        $fontFamily = intval($fontFamily);
        return imagestring($this->image, $fontFamily, $x, $y, $text, $color);
    }

    /**
     * 画线条
     * @param $x
     * @param $y
     * @param $x2
     * @param $y2
     * @param $color
     * @return $this
     * @throws \Zodream\Infrastructure\Error\Exception
     */
	public function line($x, $y, $x2, $y2, $color) {
	    imageline($this->image, $x, $y, $x2, $y2, $this->getColorWithRGB($color));
	    return $this;
    }

    /**
     * 填充颜色
     * @param int $x
     * @param int $y
     * @param int $x2
     * @param int $y2
     * @param string $color
     * @throws \Zodream\Infrastructure\Error\Exception
     */
    public function fill($x = 0, $y = 0, $x2 = 0, $y2 = 0, $color = '#fff') {
	    if (func_num_args() === 0) {
	        $x = 0;
            $y = $this->height;
            $x2 = $this->width;
            $y2 = 0;
        } elseif (!is_numeric($x)) {
	        $color = $x;
            $x = 0;
            $y = $this->height;
            $x2 = $this->width;
            $y2 = 0;
        }
        imagefilledrectangle(
            $this->image,
            $x,
            $y,
            $x2,
            $y2,
            $this->getColorWithRGB($color)
        );
    }

	/**
	 * 复制并调整图片的一部分
	 * @param Image $srcImage 本图
	 * @param int $srcX
	 * @param int $srcY
	 * @param int $x
	 * @param int $y
	 * @param int $srcWidth 如果是0则取原图的宽
	 * @param int $srcHeight 如果是0则取原图的高
	 * @param int $width 如果是0则取本图的宽
	 * @param int $height 如果是0则取本图的高
	 * @return bool
	 */
	public function copyFromWithResize(Image $srcImage, $srcX = 0, $srcY = 0, $x = 0, $y = 0, $srcWidth = 0, $srcHeight = 0, $width = 0, $height = 0) {
		if (empty($srcWidth)) {
			$srcWidth = $srcImage->getWidth();
		}
		if (empty($srcHeight)) {
			$srcHeight = $srcImage->getHeight();
		}
		if (empty($width)) {
			$width = $this->getWidth();
		}
		if (empty($height)) {
			$height = $this->getHeight();
		}
		return imagecopyresized($this->image, $srcImage->image, $x, $y, $srcX, $srcY, $width, $height, $srcWidth, $srcHeight);
	}

	/**
	 * 按照宽比缩放
	 * @param int $width
	 * @return bool
	 */
	public function scaleByWidth($width) {
		return $this->scale($width, $this->height * $width / $this->width);
	}

	/**
	 * 按照高比缩放
	 * @param int $height
	 * @return bool
	 */
	public function scaleByHeight($height) {
		return $this->scale($this->width * $height / $this->height, $height);
	}

	/**
	 * 缩放图片
	 * @param int $width
	 * @param int $height
	 * @return bool
	 */
	public function scale($width, $height) {
		$image = new Image();
		$image->create($width, $height, $this->type);

		$result = $image->copyFromWithReSampling($this);

        $this->close();

		$this->image = $image->image;
		$image->setEmptyImage();
		$this->width = $width;
		$this->height = $height;
		return $result;
	}

	/**
	 * 翻转
	 * @param bool $isX 是否沿X轴翻转， Y轴
	 */
	public function turn($isX = true) {
		$image = new Image();
		$image->create($this->width, $this->height, $this->type);
		if ($isX) {
			for($y = 0; $y < $this->height; $y ++){
				//逐条复制图片本身高度，1个像素宽度的图片到薪资源中
				$image->copyFrom($this, 0, $y, 0, $this->height - $y - 1, $this->width, 1);
			}
		} else {
			for($x = 0; $x < $this->width; $x ++){
				$image->copyFrom($this, $x, 0, $this->width - $x - 1, 0, 1, $this->height);
			}
		}
		$this->close();
		$this->image = $image->image;
        $image->setEmptyImage();
	}

	/**
	 * 图片旋转
	 * @param float|int $angle
	 * @param string|int $background
	 * @param int $ignore 如果被设为非零值，则透明色会被忽略（否则会被保留）。
	 * @return bool 失败时返回 FALSE。
	 */
	public function rotate($angle = 90, $background = '#fff', $ignore = 0) {
		$this->image = imagerotate($this->image, $angle, $this->getColorWithRGB($background), $ignore);
		return $this->image !== false;
	}

	/**
     * 保存，如果路径不存在则输出
	 * @return bool
	 */
	public function save() {
		return $this->saveAs($this->file);
	}

	/**
	 * 另存为
	 * @param string|null $output 如果为null 表示输出
	 * @param string $type
	 * @return bool
	 */
    public function saveAs($output = null, $type = null) {
        $this->setRealType($type);
        if (!is_null($output)) {
            $output = (string)$output;
        }
        return call_user_func('image'.$this->realType, $this->image, $output);
    }

    /**
     * 转化成base64编码
     * @return string
     */
    public function toBase64() {
        ob_start ();
        $this->saveAs();
        $data = ob_get_contents();
        ob_end_clean();
        return 'data:image/'.$this->realType.';base64,'.base64_encode($data);
    }
	
	public function close() {
		if (!empty($this->image)) {
			imagedestroy($this->image);
		}
        $this->image = null;
	}

	/**
	 * 验证图片是否合法
	 * @param string $file
	 * @return bool
	 */
	protected function check($file) {
		return is_file($file) && getimagesize($file) && extension_loaded('gd');
	}
	
	public function __destruct() {
		$this->close();
	}
}