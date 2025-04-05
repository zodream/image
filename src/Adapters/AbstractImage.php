<?php
declare(strict_types=1);
namespace Zodream\Image\Adapters;

use Zodream\Disk\File;
use Zodream\Image\Base\Box;
use Zodream\Image\Base\BoxInterface;

abstract class AbstractImage implements ImageAdapter {
    const array ALLOW_TYPES = array(
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

    protected string|null $file = null;

    protected int $width;

    protected int $height;

    protected string|null $type;

    protected string|null $realType;

    protected $resource;

    public function loadResource(mixed $file) {
        if (is_null($file)) {
            return $this;
        }
        if ($file instanceof File) {
            $this->open((string)$file);
            return $this;
        }
        if (is_string($file) && is_file($file)) {
            $this->open($file);
            return $this;
        }
        if (is_string($file)) {
            $this->load($file);
            return $this;
        }
        $this->read($file);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getResource() {
        return $this->resource;
    }

    public function setEmptyImage() {
        $this->resource = null;
        return $this;
    }

    public function getHeight(): int {
        return $this->height;
    }

    public function getWidth(): int {
        return $this->width;
    }

    public function getSize(): BoxInterface {
        return new Box($this->getWidth(), $this->getHeight());
    }

    public function getRealType(): string {
        return $this->realType;
    }

    /**
     * 设置真实类型
     * @param $type
     * @return static
     */
    public function setRealType(string $type) {
        if (empty($type)) {
            return $this;
        }
        foreach (self::ALLOW_TYPES as $key => $item) {
            if ((!is_array($item) && $item == $type)
                || (is_array($item) && in_array($type, $item))) {
                $this->realType = $type;
                return $this;
            }
        }
        return $this;
    }

    public function save() {
        return $this->saveAs($this->file);
    }

    public function toBase64(): string {
        ob_start ();
        $this->saveAs();
        $data = ob_get_contents();
        ob_end_clean();
        return 'data:image/'.$this->getRealType().';base64,'.base64_encode($data);
    }
}