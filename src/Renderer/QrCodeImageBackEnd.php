<?php
declare(strict_types=1);
namespace Zodream\Image\Renderer;

use BaconQrCode\Exception\RuntimeException;
use BaconQrCode\Renderer\Color\Alpha;
use BaconQrCode\Renderer\Color\ColorInterface;
use BaconQrCode\Renderer\Image\ImageBackEndInterface;
use BaconQrCode\Renderer\Image\TransformationMatrix;
use BaconQrCode\Renderer\Path\Close;
use BaconQrCode\Renderer\Path\Curve;
use BaconQrCode\Renderer\Path\EllipticArc;
use BaconQrCode\Renderer\Path\Line;
use BaconQrCode\Renderer\Path\Move;
use BaconQrCode\Renderer\Path\Path;
use BaconQrCode\Renderer\RendererStyle\Gradient;
use Zodream\Image\Adapters\ImageAdapter;
use Zodream\Image\Base\Box;
use Zodream\Image\Base\Point;
use Zodream\Image\ImageManager;

class QrCodeImageBackEnd implements ImageBackEndInterface {

    /**
     * @var ImageAdapter|null
     */
    private ?ImageAdapter $image;

    /**
     * @var int|null
     */
    private ?int $gradientCount;

    /**
     * @var TransformationMatrix[]|null
     */
    private ?array $matrices;

    /**
     * @var int|null
     */
    private ?int $matrixIndex;

    private Point $center;

    public function __construct(
        private string $imageFormat = 'png',
        private int $compressionQuality = 100)
    {
        $this->center = new Point(0, 0);
    }

    public function new(int $size, ColorInterface $backgroundColor): void
    {
        $this->image = ImageManager::create();
        $this->image->create(new Box($size, $size), $this->getColorPixel($backgroundColor));
        $this->image->setRealType($this->imageFormat);
        // $this->image->setCompressionQuality($this->compressionQuality);
        $this->gradientCount = 0;
        $this->matrices = [new TransformationMatrix()];
        $this->matrixIndex = 0;
    }

    public function scale(float $size): void
    {
        $this->image->scale(new Box($size, $size));
        $this->matrices[$this->matrixIndex] = $this->matrices[$this->matrixIndex]
            ->multiply(TransformationMatrix::scale($size));
    }

    public function translate(float $x, float $y): void
    {
        $this->center = new Point(intval($x), intval($y));
        $this->matrices[$this->matrixIndex] = $this->matrices[$this->matrixIndex]
            ->multiply(TransformationMatrix::translate($x, $y));
    }

    public function rotate(int $degrees): void
    {
        $this->image->rotate($degrees);
        $this->matrices[$this->matrixIndex] = $this->matrices[$this->matrixIndex]
            ->multiply(TransformationMatrix::rotate($degrees));
    }

    public function push(): void
    {
        $this->matrices[++$this->matrixIndex] = $this->matrices[$this->matrixIndex - 1];
    }

    public function pop(): void
    {
        unset($this->matrices[$this->matrixIndex--]);
    }

    public function drawPathWithColor(Path $path, ColorInterface $color): void
    {
        $color = $this->getColorPixel($color);
        $start = $this->center;
        $first = null;
        foreach ($path as $op) {
            switch (true) {
                case $op instanceof Move:
                    $start = new Point(intval($op->getX()), intval($op->getY()));
                    if (!$first) {
                        $first = $start;
                    }
                    break;

                case $op instanceof Line:
                    $end = new Point(intval($op->getX()), intval($op->getY()));
                    $this->image->line($start, $end, $color);
                    $start = $end;
                    break;

                case $op instanceof EllipticArc:
                    $end = new Point(intval($op->getX()), intval($op->getY()));
                    $this->image->ellipse(
                        new Point(
                            min($end->getX(), $start->getX()) + $op->getXRadius(),
                            min($end->getY(), $start->getY()) + $op->getYRadius(),
                        ),
                        new Box($op->getXRadius() * 2, $op->getYRadius() * 2),
                        $color);
                    $start = $end;
                    break;

                case $op instanceof Curve:
//                    $this->draw->pathCurveToAbsolute(
//                        $op->getX1(),
//                        $op->getY1(),
//                        $op->getX2(),
//                        $op->getY2(),
//                        $op->getX3(),
//                        $op->getY3()
//                    );
                    break;

                case $op instanceof Close:
                    $this->image->line($first, $start, $color);
                    break;
                default:
                    throw new RuntimeException('Unexpected draw operation: ' . get_class($op));
            }
        }
    }

    public function drawPathWithGradient(Path $path, Gradient $gradient, float $x, float $y, float $width, float $height): void
    {
        // TODO: Implement drawPathWithGradient() method.
    }

    public function done(): string
    {
        ob_start();
        $this->image->saveAs();
        $blob = ob_get_contents();
        ob_end_clean();
        $this->image = null;
        $this->gradientCount = null;
        return $blob;
    }

    private function getColorPixel(ColorInterface $color) : array
    {
        $alpha = 100;

        if ($color instanceof Alpha) {
            $alpha = $color->getAlpha();
            $color = $color->getBaseColor();
        }
        $rgb = $color->toRgb();

        return [$rgb->getRed(), $rgb->getGreen(), $rgb->getBlue(), $alpha / 100];
    }
}