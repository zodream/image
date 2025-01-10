<?php
declare(strict_types=1);
namespace Zodream\Image;

use Zodream\Image\Adapters\ImageAdapter;
use Zodream\Image\Base\Box;
use Zodream\Image\Base\Font;
use Zodream\Image\Base\Point;

class HintCaptcha extends Image implements ICaptcha {

    protected array $configs = [
        'count' => 3,
    ];

    /**
     * @var integer[]
     */
    protected array $point = [];

    /**
     * @var ImageAdapter
     */
    protected ImageAdapter|null $shapeImage = null;

    public function isOnlyImage(): bool {
        return false;
    }

    public function setConfigs(array $configs): void {
        $this->configs = array_merge($this->configs, $configs);
    }

    public function generate(): mixed {
        $this->instance()->scale(new Box($this->configs['width'],
            $this->configs['height']));
        $this->drawBox();
        return $this->point;
    }

    public function drawBox(): void {
        $image = $this->instance();
        $width = $image->getWidth();
        $height = $image->getHeight();
        $font = $this->configs['fontFamily'];
        $fontSize = $this->configs['fontSize'];
        $words = $this->configs['words'];
        $count = $this->configs['count'];
        $maxWidth = $width - $fontSize;
        $maxHeight = $height - $fontSize;
        $this->shapeImage = ImageManager::create()->create(new Box($count * $fontSize, $fontSize), '#fff');
        $darkFont = new Font($font, intval($fontSize * .6), '#000');
        $items = [];
        foreach ($words as $i => $word) {
            $x = random_int($fontSize, $maxWidth - $fontSize);
            $y = random_int($fontSize, $maxHeight - $fontSize);
            $this->instance()->char($word, new Font($font, $fontSize, '#000'),
                new Point(
                    $x, $y
                ));
            if ($count > $i) {
                $this->shapeImage->char($word, $darkFont, new Point($i * $fontSize, $darkFont->getSize()));
                $items[] = [
                    $x, $y
                ];
            }
        }
        $this->point = $items;
    }

    public function verify(mixed $value, mixed $source): bool {
        if (empty($value) || empty($source)) {
            return false;
        }
        $size = intval($this->configs['fontSize']);
        foreach ($source as $i => $p) {
            if (!isset($value[$i])) {
                return false;
            }
            $srcX = ImageHelper::x($p);
            $srcY = ImageHelper::y($p);
            if (
                !ImageHelper::inBound($value[$i], $srcX, $srcY, $size, $size)
            ) {
                return false;
            }
        }
        return true;
    }

    public function toArray(): array {
        return [
            'image' => $this->toBase64(),
            'width' => $this->instance()->getWidth(),
            'height' => $this->instance()->getHeight(),
            'count' => $this->configs['count'],
            'control' => $this->shapeImage->toBase64()
        ];
    }
}