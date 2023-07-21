<?php
declare(strict_types=1);
namespace Zodream\Image\Adapters;

use InvalidArgumentException;
use OutOfBoundsException;
use RuntimeException;
use Zodream\Image\Base\Box;
use Zodream\Image\Base\BoxInterface;
use Zodream\Image\Base\FontInterface;
use Zodream\Image\Base\Matrix;
use Zodream\Image\Base\Point;
use Zodream\Image\Base\PointInterface;
use Zodream\Image\Colors;
use Zodream\Image\ImageManager;

/**
 * 查考 Imagine 作了适配
 * @see https://github.com/php-imagine/Imagine
 */
class Imagick extends AbstractImage implements ImageAdapter {

    /**
     * @var \Imagick
     */
    protected $resource;

    /**
     * {@inheritdoc}
     *
     */
    public function open(mixed $path)
    {
        try {
            $this->resource = new \Imagick((string)$path);
            $this->refreshMeta();
        } catch (\ImagickException $e) {
            throw new RuntimeException(sprintf('Unable to open image %s', $path), $e->getCode(), $e);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     */
    public function create(BoxInterface $size, $color = null)
    {
        $width = $size->getWidth();
        $height = $size->getHeight();


        if (empty($color)) {
            $color = '#fff';
        }

        try {

            $pixel = $this->converterToColor($color);
            $imagick = new \Imagick();
            $imagick->newImage($width, $height, $pixel);
            $imagick->setImageMatte(true);

            $imagick->setImageBackgroundColor($pixel);

            // $imagick->setImageAlpha($pixel->getColorValue(\Imagick::COLOR_ALPHA));

            $pixel->clear();
            $pixel->destroy();
            $this->resource = $imagick;
            $this->refreshMeta();
            return $this;
        } catch (\ImagickException $e) {
            throw new RuntimeException('Could not create empty image', $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function load(string $string)
    {
        try {
            $imagick = new \Imagick();

            $imagick->readImageBlob($string);
            $imagick->setImageMatte(true);
            $this->resource = $imagick;
            $this->refreshMeta();
            return $this;
        } catch (\ImagickException $e) {
            throw new RuntimeException('Could not load image from string', $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     *
     */
    public function read(mixed $resource)
    {
        if (!is_resource($resource)) {
            throw new InvalidArgumentException('Variable does not contain a stream resource');
        }

        $content = stream_get_contents($resource);

        try {
            $imagick = new \Imagick();
            $imagick->readImageBlob($content);
            $this->resource = $imagick;
            $this->refreshMeta();
        } catch (\ImagickException $e) {
            throw new RuntimeException('Could not read image from resource', $e->getCode(), $e);
        }

        return $this;
    }

    protected function refreshMeta() {
        $this->width = $this->resource->getImageWidth();
        $this->height = $this->resource->getImageHeight();
        if (empty($this->realType)) {
            $this->setRealType('png');
        }
    }

    /**
     * {@inheritdoc}
     *
     */
    public function arc(PointInterface $center, BoxInterface  $size, int $start, int $end, $color, int $thickness = 1)
    {
        $thickness = max(0, (int) round($thickness));
        if ($thickness === 0) {
            return $this;
        }
        $x = $center->getX();
        $y = $center->getY();
        $width = $size->getWidth();
        $height = $size->getHeight();

        try {
            $pixel = $this->converterToColor($color);
            $arc = new \ImagickDraw();

            $arc->setStrokeColor($pixel);
            $arc->setStrokeWidth($thickness);
            $arc->setFillColor('transparent');
            $arc->arc(
                $x - $width / 2,
                $y - $height / 2,
                $x + $width / 2,
                $y + $height / 2,
                $start, $end
            );

            $this->resource->drawImage($arc);

            $pixel->clear();
            $pixel->destroy();

            $arc->clear();
            $arc->destroy();
        } catch (\ImagickException $e) {
            throw new RuntimeException('Draw arc operation failed', $e->getCode(), $e);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     */
    public function chord(PointInterface $center, BoxInterface  $size, int $start, int $end, $color, bool $fill = false, int $thickness = 1)
    {
        $thickness = max(0, (int) round($thickness));
        if ($thickness === 0 && !$fill) {
            return $this;
        }
        $x = $center->getX();
        $y = $center->getY();
        $width = $size->getWidth();
        $height = $size->getHeight();

        try {
            $pixel = $this->converterToColor($color);
            $chord = new \ImagickDraw();

            $chord->setStrokeColor($pixel);
            $chord->setStrokeWidth($thickness);

            if ($fill) {
                $chord->setFillColor($pixel);
            } else {
                $from = new Point(
                    (int)round($x + $width / 2 * cos(deg2rad($start))),
                    (int)round($y + $height / 2 * sin(deg2rad($start))));
                $to = new Point(
                    (int)round($x + $width / 2 * cos(deg2rad($end))),
                    (int)round($y + $height / 2 * sin(deg2rad($end))));
                $this->line($from, $to, $color, $thickness);
                $chord->setFillColor('transparent');
            }

            $chord->arc(
                $x - $width / 2,
                $y - $height / 2,
                $x + $width / 2,
                $y + $height / 2,
                $start,
                $end
            );

            $this->resource->drawImage($chord);

            $pixel->clear();
            $pixel->destroy();

            $chord->clear();
            $chord->destroy();
        } catch (\ImagickException $e) {
            throw new RuntimeException('Draw chord operation failed', $e->getCode(), $e);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     */
    public function circle(PointInterface $center, int|float $radius, $color, bool $fill = false, int $thickness = 1)
    {
        $diameter = $radius * 2;

        return $this->ellipse($center, new Box($diameter, $diameter), $color, $fill, $thickness);
    }

    /**
     * {@inheritdoc}
     *
     */
    public function ellipse(PointInterface $center, BoxInterface $size, $color, bool $fill = false, int $thickness = 1)
    {
        $thickness = max(0, (int) round($thickness));
        if ($thickness === 0 && !$fill) {
            return $this;
        }
        $width = $size->getWidth();
        $height = $size->getHeight();
        try {
            $pixel = $this->converterToColor($color);
            $ellipse = new \ImagickDraw();

            $ellipse->setStrokeColor($pixel);
            $ellipse->setStrokeWidth($thickness);

            if ($fill) {
                $ellipse->setFillColor($pixel);
            } else {
                $ellipse->setFillColor('transparent');
            }

            $ellipse->ellipse(
                $center->getX(),
                $center->getY(),
                $width / 2,
                $height / 2,
                0, 360
            );

            if (false === $this->resource->drawImage($ellipse)) {
                throw new RuntimeException('Ellipse operation failed');
            }

            $pixel->clear();
            $pixel->destroy();

            $ellipse->clear();
            $ellipse->destroy();
        } catch (\ImagickException $e) {
            throw new RuntimeException('Draw ellipse operation failed', $e->getCode(), $e);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     */
    public function line(PointInterface $start, PointInterface $end, $outline, int $thickness = 1)
    {
        $thickness = max(0, (int) round($thickness));
        if ($thickness === 0) {
            return $this;
        }
        try {
            $pixel = $this->converterToColor($outline);
            $line = new \ImagickDraw();

            $line->setStrokeColor($pixel);
            $line->setStrokeWidth($thickness);
            $line->setFillColor($pixel);
            $line->line(
                $start->getX(),
                $start->getY(),
                $end->getX(),
                $end->getY()
            );

            $this->resource->drawImage($line);

            $pixel->clear();
            $pixel->destroy();

            $line->clear();
            $line->destroy();
        } catch (\ImagickException $e) {
            throw new RuntimeException('Draw line operation failed', $e->getCode(), $e);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     */
    public function pieSlice(PointInterface $center, BoxInterface  $size, int $start, int $end, $color, bool $fill = false, int $thickness = 1)
    {
        $thickness = max(0, (int)round($thickness));
        if ($thickness === 0 && !$fill) {
            return $this;
        }
        $width = $size->getWidth();
        $height = $size->getHeight();

        $x1 = round($center->getX() + $width / 2 * cos(deg2rad($start)));
        $y1 = round($center->getY() + $height / 2 * sin(deg2rad($start)));
        $x2 = round($center->getX() + $width / 2 * cos(deg2rad($end)));
        $y2 = round($center->getY() + $height / 2 * sin(deg2rad($end)));

        if ($fill) {
            $this->chord($center, $size, $start, $end, $color, true, $thickness);
            $this->polygon(
                array(
                    $center,
                    new Point((int)$x1, (int)$y1),
                    new Point((int)$x2, (int)$y2),
                ),
                $color,
                true,
                $thickness
            );
        } else {
            $this->arc($center, $size, $start, $end, $color, $thickness);
            $this->line($center, new Point((int)$x1, (int)$y1), $color, $thickness);
            $this->line($center, new Point((int)$x2, (int)$y2), $color, $thickness);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     */
    public function dot(PointInterface $position, $color)
    {
        $x = $position->getX();
        $y = $position->getY();

        try {
            $pixel = $this->converterToColor($color);
            $point = new \ImagickDraw();

            $point->setFillColor($pixel);
            $point->point($x, $y);

            $this->resource->drawimage($point);

            $pixel->clear();
            $pixel->destroy();

            $point->clear();
            $point->destroy();
        } catch (\ImagickException $e) {
            throw new RuntimeException('Draw point operation failed', $e->getCode(), $e);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     */
    public function rectangle(PointInterface $leftTop, PointInterface $rightBottom, $color, bool $fill = false, int $thickness = 1)
    {
        $thickness = max(0, (int) round($thickness));
        if ($thickness === 0 && !$fill) {
            return $this;
        }
        $minX = min($leftTop->getX(), $rightBottom->getX());
        $maxX = max($leftTop->getX(), $rightBottom->getX());
        $minY = min($leftTop->getY(), $rightBottom->getY());
        $maxY = max($leftTop->getY(), $rightBottom->getY());

        try {
            $pixel = $this->converterToColor($color);
            $rectangle = new \ImagickDraw();
            $rectangle->setStrokeColor($pixel);
            $rectangle->setStrokeWidth($thickness);

            if ($fill) {
                $rectangle->setFillColor($pixel);
            } else {
                $rectangle->setFillColor('transparent');
            }

            $rectangle->rectangle($minX, $minY, $maxX, $maxY);
            $this->resource->drawImage($rectangle);

            $pixel->clear();
            $pixel->destroy();

            $rectangle->clear();
            $rectangle->destroy();
        } catch (\ImagickException $e) {
            throw new RuntimeException('Draw rectangle operation failed', $e->getCode(), $e);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     */
    public function polygon(array $coordinates, $color, bool $fill = false, int $thickness = 1)
    {
        if (count($coordinates) < 3) {
            throw new InvalidArgumentException(sprintf('Polygon must consist of at least 3 coordinates, %d given', count($coordinates)));
        }

        $thickness = max(0, (int) round($thickness));
        if ($thickness === 0 && !$fill) {
            return $this;
        }
        $points = array_map(function (PointInterface $p) {
            return array('x' => $p->getX(), 'y' => $p->getY());
        }, $coordinates);

        try {
            $pixel = $this->converterToColor($color);
            $polygon = new \ImagickDraw();

            $polygon->setStrokeColor($pixel);
            $polygon->setStrokeWidth($thickness);

            if ($fill) {
                $polygon->setFillColor($pixel);
            } else {
                $polygon->setFillColor('transparent');
            }

            $polygon->polygon($points);
            $this->resource->drawImage($polygon);

            $pixel->clear();
            $pixel->destroy();

            $polygon->clear();
            $polygon->destroy();
        } catch (\ImagickException $e) {
            throw new RuntimeException('Draw polygon operation failed', $e->getCode(), $e);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     */
    public function text(string $string, FontInterface $font, PointInterface $position, int|float $angle = 0, int $width = 0)
    {
        try {
            $pixel = $this->converterToColor($font->getColor());
            $text = new \ImagickDraw();

            $text->setFont($font->getFile());
            /*
             * @see http://www.php.net/manual/en/imagick.queryfontmetrics.php#101027
             *
             * ensure font resolution is the same as GD's hard-coded 96
             */
            if (version_compare(phpversion('imagick'), '3.0.2', '>=')) {
                $text->setResolution(96, 96);
                $text->setFontSize($font->getSize());
            } else {
                $text->setFontSize((int) ($font->getSize() * (96 / 72)));
            }
            $text->setFillColor($pixel);
            $text->setTextAntialias(true);

            if ($width !== 0) {
                $string = $font->wrapText($string, $width, $angle);
            }

            $info = $this->resource->queryFontMetrics($text, $string);
            $rad = deg2rad($angle);
            $cos = cos($rad);
            $sin = sin($rad);

            // round(0 * $cos - 0 * $sin)
            $x1 = 0;
            $x2 = round($info['characterWidth'] * $cos - $info['characterHeight'] * $sin);
            // round(0 * $sin + 0 * $cos)
            $y1 = 0;
            $y2 = round($info['characterWidth'] * $sin + $info['characterHeight'] * $cos);

            $xdiff = 0 - min($x1, $x2);
            $ydiff = 0 - min($y1, $y2);

            $this->resource->annotateImage(
                $text, $position->getX(), //+ $x1 + $xdiff,
                $position->getY()/* + $y2 + $ydiff*/, $angle, $string
            );

            $pixel->clear();
            $pixel->destroy();

            $text->clear();
            $text->destroy();
        } catch (\ImagickException $e) {
            throw new RuntimeException('Draw text operation failed', $e->getCode(), $e);
        }

        return $this;
    }

    public function char(string|int $code, FontInterface $font, PointInterface $position, int|float $angle = 0) {
        return $this->text(is_int($code) ? mb_chr($code, 'UTF-8') : $code, $font, $position, $angle, 0);
    }

    public function fontSize(string $string, FontInterface $font, int|float $angle = 0)
    {
        $text = new \ImagickDraw();

        $text->setFont($font->getFile());

        /*
         * @see http://www.php.net/manual/en/imagick.queryfontmetrics.php#101027
         *
         * ensure font resolution is the same as GD's hard-coded 96
         */
        if (version_compare(phpversion('imagick'), '3.0.2', '>=')) {
            $text->setResolution(96, 96);
            $text->setFontSize($font->getSize());
        } else {
            $text->setFontSize((int) ($font->getSize() * (96 / 72)));
        }

        $info = $this->resource->queryFontMetrics($text, $string);
        return new Box($info['textWidth'], $info['textHeight']);
    }

    /**
     * {@inheritdoc}
     *
     */
    public function gamma(float $correction)
    {
        try {
            $this->resource->gammaImage($correction, \Imagick::CHANNEL_ALL);
        } catch (\ImagickException $e) {
            throw new RuntimeException('Failed to apply gamma correction to the image', $e->getCode(), $e);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     */
    public function negative()
    {
        try {
            $this->resource->negateImage(false, \Imagick::CHANNEL_ALL);
        } catch (\ImagickException $e) {
            throw new RuntimeException('Failed to negate the image', $e->getCode(), $e);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     */
    public function grayscale()
    {
        try {
            $this->resource->setImageType(\Imagick::IMGTYPE_GRAYSCALE);
        } catch (\ImagickException $e) {
            throw new RuntimeException('Failed to grayscale the image', $e->getCode(), $e);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     * @param array|string $color
     */
    public function colorize(mixed $color)
    {
        if (!is_array($color)) {
            $color = Colors::transformRGB($color);
        }
        if (!is_array($color) || count($color) < 3) {
            throw new \Exception('color is not [R,G,B]');
        }
        try {
            $this->resource->colorizeImage($this->converterToColor($color),
                new \ImagickPixel(sprintf('rgba(%d, %d, %d, 1)', $color[0], $color[1], $color[2])));
        } catch (\ImagickException $e) {
            throw new RuntimeException('Failed to colorize the image', $e->getCode(), $e);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     */
    public function sharpen()
    {
        try {
            $this->resource->sharpenImage(2, 1);
        } catch (\ImagickException $e) {
            throw new RuntimeException('Failed to sharpen the image', $e->getCode(), $e);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     */
    public function blur(float $sigma = 1)
    {
        try {
            $this->resource->gaussianBlurImage(0, $sigma);
        } catch (\ImagickException $e) {
            throw new RuntimeException('Failed to blur the image', $e->getCode(), $e);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     */
    public function brightness(float $brightness)
    {
        $brightness = (int) round($brightness);
        if ($brightness < -100 || $brightness > 100) {
            throw new InvalidArgumentException(sprintf('The %1$s argument can range from %2$d to %3$d, but you specified %4$d.', '$brightness', -100, 100, $brightness));
        }
        try {
            if (method_exists($this->resource, 'brightnesscontrastimage')) {
                // Available since Imagick 3.3.0
                $this->resource->brightnesscontrastimage($brightness, 0);
            } else {
                // This *emulates* brightnesscontrastimage
                $sign = $brightness < 0 ? -1 : 1;
                $v = abs($brightness) / 100;
                $v = (1 / (sin(($v * .99999 * M_PI_2) + M_PI_2))) - 1;
                $this->resource->modulateimage(100 + $sign * $v * 100, 100, 100);
            }
        } catch (\ImagickException $e) {
            throw new RuntimeException('Failed to brightness the image');
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     */
    public function convolve(Matrix $matrix)
    {
        if ($matrix->getWidth() !== 3 || $matrix->getHeight() !== 3) {
            throw new InvalidArgumentException(sprintf('A convolution matrix must be 3x3 (%dx%d provided).', $matrix->getWidth(), $matrix->getHeight()));
        }
        try {
            $this->resource->convolveImage($matrix->getValueList());
        } catch (\ImagickException $e) {
            throw new RuntimeException('Failed to convolve the image');
        }

        return $this;
    }

    public function copy()
    {
        try {
            return clone $this;
        } catch (\ImagickException $e) {
            throw new RuntimeException('Copy operation failed', $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     *
     */
    public function paste(ImageAdapter $image, PointInterface $start, int|float $alpha = 100)
    {
        if (!$image instanceof self) {
            throw new InvalidArgumentException(sprintf('Imagick\Image can only paste() Imagick\Image instances, %s given', get_class($image)));
        }

        $alpha = (int) round($alpha);
        if ($alpha < 0 || $alpha > 100) {
            throw new InvalidArgumentException(sprintf('The %1$s argument can range from %2$d to %3$d, but you specified %4$d.', '$alpha', 0, 100, $alpha));
        }

        if ($alpha === 100) {
            $pasteMe = $image->resource;
        } elseif ($alpha > 0) {
            $pasteMe = $image->cloneImagick();
            // setImageOpacity was replaced with setImageAlpha in php-imagick v3.4.3
            if (method_exists($pasteMe, 'setImageAlpha')) {
                $pasteMe->setImageAlpha($alpha / 100);
            } else {
                $pasteMe->setImageOpacity($alpha / 100);
            }
        } else {
            $pasteMe = null;
        }
        if ($pasteMe !== null) {
            try {
                $this->resource->compositeImage($pasteMe, \Imagick::COMPOSITE_DEFAULT, $start->getX(), $start->getY());
                $error = null;
            } catch (\ImagickException $e) {
                $error = $e;
            }
            if ($pasteMe !== $image->resource) {
                $pasteMe->clear();
                $pasteMe->destroy();
            }
            if ($error !== null) {
                throw new RuntimeException('Paste operation failed', $error->getCode(), $error);
            }
        }

        return $this;
    }

    public function pastePart(ImageAdapter $src, PointInterface $srcStart, BoxInterface $srcBox, PointInterface $start, BoxInterface $box = null, int|float $alpha = 100)
    {
        // TODO: Implement pastePart() method.
    }

    /**
     * {@inheritdoc}
     *
     */
    public function resize(BoxInterface $size, $filter = ImageAdapter::FILTER_UNDEFINED)
    {
        try {
            if ($this->resource->count() > 1) {
                $this->resource = $this->resource->coalesceImages();
                foreach ($this->resource as $frame) {
                    $frame->resizeImage($size->getWidth(), $size->getHeight(), $this->getFilter($filter), 1);
                }
                $this->resource = $this->resource->deconstructImages();
            } else {
                $this->resource->resizeImage($size->getWidth(), $size->getHeight(), $this->getFilter($filter), 1);
            }
        } catch (\ImagickException $e) {
            throw new RuntimeException('Resize operation failed', $e->getCode(), $e);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     */
    public function rotate($angle, $background = null)
    {
        if ($background === null) {
            $background = '#fff';
        }

        try {
            $pixel = $this->converterToColor($background);

            $this->resource->rotateimage($pixel, $angle);

            $pixel->clear();
            $pixel->destroy();
        } catch (\ImagickException $e) {
            throw new RuntimeException('Rotate operation failed', $e->getCode(), $e);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     */
    public function save()
    {
        $path = $this->file;
        if (null === $path) {
            throw new RuntimeException('You can omit save path only if image has been open from a file');
        }

        try {
            $this->resource->writeImages($path, true);
        } catch (\ImagickException $e) {
            throw new RuntimeException('Save operation failed', $e->getCode(), $e);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     */
    public function __clone()
    {
        if ($this->resource instanceof \Imagick) {
            $this->resource = $this->cloneImagick();
            $this->refreshMeta();
        }
    }

    /**
     * Destroys allocated imagick resources.
     */
    public function __destruct()
    {
        if ($this->resource instanceof \Imagick) {
            $this->resource->clear();
            $this->resource->destroy();
        }
    }

    /**
     * Gets specifically formatted color string from ColorInterface instance.
     *
     * @return \ImagickPixel
     */
    public function converterToColor(mixed $color): \ImagickPixel
    {
        if ($color instanceof \ImagickPixel) {
            return $color;
        }
        $args = Colors::converter($color);
        if (!is_array($args)) {
            $temp = ImageManager::create('gd')->create(new Box(10, 10));
            $args = $temp->converterFromColor($color);
            unset($args);
        }
        $pixel = new \ImagickPixel(sprintf('rgb(%d,%d,%d)', $args[0], $args[1], $args[2]));
        if (isset($args[3])) {
            $pixel->setColorValue(\Imagick::COLOR_ALPHA, floatval($args[3]));
        }
        return $pixel;
    }

    protected function cloneImagick()
    {
        // the clone method has been deprecated in imagick 3.1.0b1.
        // we can't use phpversion('imagick') because it may return `@PACKAGE_VERSION@`
        // so, let's check if ImagickDraw has the setResolution method, which has been introduced in the same version 3.1.0b1
        if (method_exists('ImagickDraw', 'setResolution')) {
            return clone $this->resource;
        }

        return $this->resource->clone();
    }

    public function scale(BoxInterface $box)
    {
        $this->resource->scaleImage($box->getWidth(), $box->getHeight());
        return $this;
    }

    public function getColorAt(PointInterface $point)
    {
        $areaIterator = $this->resource->getPixelRegionIterator($point->getX(), $point->getY(), 1, 1);
        foreach ($areaIterator as $rowIterator) {
            foreach ($rowIterator as $pixel) {
                $color = $pixel->getColor();
                return [$color['r'], $color['g'], $color['b'], $color['a']];
            }
        }
        return [0, 0, 0, 0];
    }

    public function crop(PointInterface $start, BoxInterface $size)
    {
        if (!$start->in($this->getSize())) {
            throw new OutOfBoundsException('Crop coordinates must start at minimum 0, 0 position from top left corner, crop height and width must be positive integers and must not exceed the current image borders');
        }
        $this->resource->cropImage($size->getWidth(), $size->getHeight(), $start->getX(), $start->getX());
        return $this;
    }

    public function saveAs(mixed $output = null, string $type = 'jpeg'): bool
    {
        $this->setRealType($type);
        $this->resource->setImageFormat($this->getRealType());
        if (is_null($output)) {
            echo $this->resource->getImageBlob();
            return true;
        }
        return $this->resource->writeImage((string)$output);
    }

    public function toBase64(): string {
        return 'data:image/'.$this->getRealType().';base64,'.base64_encode($this->resource->getImageBlob());
    }

    public function fill(mixed $color)
    {
        $this->resource->setImageBackgroundColor($this->converterToColor($color));
        return $this;
    }

    public function transparent(mixed $color)
    {
        $this->setRealType('png');
        $this->resource->setimageformat('png');
        $this->resource->transparentPaintImage(
            $this->converterToColor($color), 0, 1, false
        );
        $this->resource->despeckleimage();
        return $this;
    }

    public function thumbnail(BoxInterface $box)
    {
        $this->resource->thumbnailImage($box->getWidth(), $box->getHeight());
        return $this;
    }

    public function converterFromColor(mixed $color): mixed
    {
        if (is_array($color)) {
            return $color;
        }
        if (!$color instanceof \ImagickPixel) {
            $color = new \ImagickPixel((string)$color);
        }
        $res = $color->getColor();
        return [$res['r'], $res['g'], $res['b'], $res['a']];
    }

    private function getFilter(string $filter = ImageAdapter::FILTER_UNDEFINED): int
    {
        return match($filter) {
            ImageAdapter::FILTER_UNDEFINED => \Imagick::FILTER_UNDEFINED,
            ImageAdapter::FILTER_BESSEL => \Imagick::FILTER_BESSEL,
            ImageAdapter::FILTER_BLACKMAN => \Imagick::FILTER_BLACKMAN,
            ImageAdapter::FILTER_BOX => \Imagick::FILTER_BOX,
            ImageAdapter::FILTER_CATROM => \Imagick::FILTER_CATROM,
            ImageAdapter::FILTER_CUBIC => \Imagick::FILTER_CUBIC,
            ImageAdapter::FILTER_GAUSSIAN => \Imagick::FILTER_GAUSSIAN,
            ImageAdapter::FILTER_HANNING => \Imagick::FILTER_HANNING,
            ImageAdapter::FILTER_HAMMING => \Imagick::FILTER_HAMMING,
            ImageAdapter::FILTER_HERMITE => \Imagick::FILTER_HERMITE,
            ImageAdapter::FILTER_LANCZOS => \Imagick::FILTER_LANCZOS,
            ImageAdapter::FILTER_MITCHELL => \Imagick::FILTER_MITCHELL,
            ImageAdapter::FILTER_POINT => \Imagick::FILTER_POINT,
            ImageAdapter::FILTER_QUADRATIC => \Imagick::FILTER_QUADRATIC,
            ImageAdapter::FILTER_SINC => \Imagick::FILTER_SINC,
            ImageAdapter::FILTER_TRIANGLE => \Imagick::FILTER_TRIANGLE,
            default => throw new \Exception('error filter'),
        };
    }
}