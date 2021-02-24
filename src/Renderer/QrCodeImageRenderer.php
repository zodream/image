<?php
declare(strict_types=1);
namespace Zodream\Image\Renderer;

use BaconQrCode\Encoder\QrCode;
use BaconQrCode\Exception\InvalidArgumentException;
use BaconQrCode\Renderer\Color\Alpha;
use BaconQrCode\Renderer\Color\ColorInterface;
use BaconQrCode\Renderer\RendererInterface;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use Zodream\Image\Adapters\ImageAdapter;
use Zodream\Image\Base\Box;
use Zodream\Image\Base\Point;
use Zodream\Image\ImageManager;

class QrCodeImageRenderer implements RendererInterface {

    protected ?ImageAdapter $image;

    protected array $colors = [];

    protected int|float $blockSize;

    public function __construct(
        protected RendererStyle $rendererStyle,
    )
    {
    }

    public function render(QrCode $qrCode): string
    {
        $size = $this->rendererStyle->getSize();
        $margin = $this->rendererStyle->getMargin();
        $matrix = $qrCode->getMatrix();
        $matrixSize = $matrix->getWidth();

        if ($matrixSize !== $matrix->getHeight()) {
            throw new InvalidArgumentException('Matrix must have the same width and height');
        }
        $totalSize = $matrixSize + ($margin * 2);
        $this->blockSize = $size / $totalSize;

        $topPadding  = $leftPadding = (int) (($size - ($matrixSize * $this->blockSize)) / 2);

        $fill = $this->rendererStyle->getFill();
        $this->addColor('background', $fill->getBackgroundColor());
        $this->addColor('foreground', $fill->getForegroundColor());
        $this->image = ImageManager::create()->create(new Box($size, $size), $this->colors['background']);

        for ($inputY = 0, $outputY = $topPadding; $inputY < $matrixSize; $inputY++, $outputY += $this->blockSize) {
            for ($inputX = 0, $outputX = $leftPadding; $inputX < $matrixSize; $inputX++, $outputX += $this->blockSize) {
                if ($matrix->get($inputX, $inputY) === 1) {
                    $this->drawBlock($outputX, $outputY, 'foreground');
                }
            }
        }

        return $this->getByteStream();
    }


    public function getImage() {
        return $this->image;
    }


    protected function getByteStream(): string {
        ob_start();
        $this->image->saveAs();
        $blob = ob_get_contents();
        ob_end_clean();
        return $blob;
    }

    protected function drawBlock(int|float $x, int|float $y, string $colorId) {
        $this->image->rectangle(
            new Point((int)$x, (int)$y),
            new Point((int)($x + $this->blockSize - 1), (int)($y + $this->blockSize - 1)),
            $this->colors[$colorId],
            true,
        );
    }

    protected function addColor(string $id, ColorInterface $color) {
        $this->colors[$id] = $this->formatColor($color);
    }

    protected function formatColor(ColorInterface $color) : array {
        $alpha = 100;
        if ($color instanceof Alpha) {
            $alpha = $color->getAlpha();
            $color = $color->getBaseColor();
        }
        $rgb = $color->toRgb();
        return [$rgb->getRed(), $rgb->getGreen(), $rgb->getBlue(), $alpha / 100];
    }
}