<?php
namespace Zodream\Image;

class ImageCompare extends Image {
    /**
     * 比较两张图片是否相同
     * @param Image $image
     * @return bool
     */
	public function compare(Image $image) {
		$hash1 = $this->getHashValue();
		$hash2 = $image->getHashValue();
		if (strlen($hash1) !== strlen($hash2)) {
			return false;
		}
		$count = 0;
		$len = strlen($hash1);
		for ($i = 0; $i < $len; $i++) {
			if ($hash1[$i] !== $hash2[$i]) {
				$count++;
			}
		}
		return $count <= 10;
	}
	 
}