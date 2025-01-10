<?php
namespace Zodream\Image;

use Zodream\Image\Adapters\AbstractImage;
use Zodream\Image\Adapters\ImageAdapter;
use Zodream\Image\Base\Box;
use Zodream\Image\Base\Point;

class Image {

	protected $file;

	/**
	 * @var ImageAdapter|AbstractImage
	 */
	protected $resource;

    public function instance() {
        if ($this->resource) {
            return $this->resource;
        }
        return $this->resource = ImageManager::create();
    }

    public function getRealType() {
        return $this->resource->getRealType();
    }
	
	public function getHashValue(): string {
		$w = 8;
		$h = 8;
		$image = clone $this->instance();
		$image->scale(new Box($w, $h));
		$total = 0;
		$array = array();
		for( $y = 0; $y < $h; $y++) {
			for ($x = 0; $x < $w; $x++) {
				$gray = ($image->getColorAt(new Point($x, $y)) >> 8) & 0xFF;
				if(!isset($array[$y])) $array[$y] = array();
				$array[$y][$x] = $gray;
				$total += $gray;
			}
		}
		unset($image);
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
     * 保存，如果路径不存在则输出
	 * @return bool
	 */
	public function save() {
		return $this->instance()->save();
	}

	/**
	 * 另存为
	 * @param string|null $output 如果为null 表示输出
	 * @param string $type
	 * @return bool
	 */
    public function saveAs(string|null $output = null, string $type = '') {
        $this->instance()->saveAs($output, $type);
    }

    public function show() {
        if (!function_exists('app')) {
            throw new \Exception('not support show');
        }
        return app('response')->image($this)->send();
    }

    /**
     * 转化成base64编码
     * @return string
     */
    public function toBase64() {
        return $this->instance()->toBase64();
    }
}