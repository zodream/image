<?php
declare(strict_types=1);
namespace Zodream\Image\Adapters;

use Exception;
use GmagickPixel;
use InvalidArgumentException;
use RuntimeException;
use Zodream\Image\Base\Box;
use Zodream\Image\Base\BoxInterface;
use Zodream\Image\Base\FontInterface;
use Zodream\Image\Base\Matrix;
use Zodream\Image\Base\Point;
use Zodream\Image\Base\PointInterface;

class Gmagick extends AbstractImage implements ImageAdapter {

    /**
     * @var \Gmagick
     */
    protected $resource;

    /**
     * {@inheritdoc}
     *
     */
    public function open(mixed $path)
    {

        try {
            $this->resource = new \Gmagick((string)$path);
        } catch (\GmagickException $e) {
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
            $gmagick = new \Gmagick();
            $pixel = $this->converterToColor($color);

            $gmagick->newimage($width, $height, $pixel->getcolor(false));
            $gmagick->setimagecolorspace(\Gmagick::COLORSPACE_TRANSPARENT);
            $gmagick->setimagebackgroundcolor($pixel);

            $this->resource = $gmagick;
            $this->refreshMeta();
            return $this;
        } catch (\GmagickException $e) {
            throw new RuntimeException('Could not create empty image', $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     *
     */
    public function load($string) {
        try {
            $imagick = new \Gmagick();

            $imagick->readImageBlob($string);
            $this->resource = $imagick;
            $this->refreshMeta();
            return $this;
        } catch (\GmagickException $e) {
            throw new RuntimeException('Could not load image from string', $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     *
     */
    public function read($resource)
    {
        if (!is_resource($resource)) {
            throw new InvalidArgumentException('Variable does not contain a stream resource');
        }

        $content = stream_get_contents($resource);

        if (false === $content) {
            throw new InvalidArgumentException('Couldn\'t read given resource');
        }

        try {
            $imagick = new \Gmagick();
            $imagick->readImageBlob($content);
            $this->resource = $imagick;
            $this->refreshMeta();
        } catch (\GmagickException $e) {
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
            $arc = new \GmagickDraw();

            $arc->setstrokecolor($pixel);
            $arc->setstrokewidth($thickness);
            $arc->setfillcolor('transparent');
            $arc->arc(
                $x - $width / 2,
                $y - $height / 2,
                $x + $width / 2,
                $y + $height / 2,
                $start,
                $end
            );

            $this->resource->drawImage($arc);

            $pixel = null;

            $arc = null;
        } catch (\GmagickException $e) {
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
            $chord = new \GmagickDraw();

            $chord->setstrokecolor($pixel);
            $chord->setstrokewidth($thickness);

            if ($fill) {
                $chord->setfillcolor($pixel);
            } else {
                $x1 = (int)round($x + $width / 2 * cos(deg2rad($start)));
                $y1 = (int)round($y + $height / 2 * sin(deg2rad($start)));
                $x2 = (int)round($x + $width / 2 * cos(deg2rad($end)));
                $y2 = (int)round($y + $height / 2 * sin(deg2rad($end)));

                $this->line(new Point($x1, $y1), new Point($x2, $y2), $color, $thickness);

                $chord->setfillcolor('transparent');
            }

            $chord->arc($x - $width / 2, $y - $height / 2, $x + $width / 2, $y + $height / 2, $start, $end);

            $this->resource->drawImage($chord);

            $pixel = null;

            $chord = null;
        } catch (\GmagickException $e) {
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
            $ellipse = new \GmagickDraw();

            $ellipse->setstrokecolor($pixel);
            $ellipse->setstrokewidth($thickness);

            if ($fill) {
                $ellipse->setfillcolor($pixel);
            } else {
                $ellipse->setfillcolor('transparent');
            }

            $ellipse->ellipse(
                $center->getX(),
                $center->getY(),
                $width / 2,
                $height / 2,
                0, 360
            );

            $this->resource->drawImage($ellipse);

            $pixel = null;

            $ellipse = null;
        } catch (\GmagickException $e) {
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
            $line = new \GmagickDraw();

            $line->setstrokecolor($pixel);
            $line->setstrokewidth($thickness);
            $line->setfillcolor($pixel);
            $line->line(
                $start->getX(),
                $start->getY(),
                $end->getX(),
                $end->getY()
            );

            $this->resource->drawImage($line);

            $pixel = null;

            $line = null;
        } catch (\GmagickException $e) {
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
        $thickness = max(0, (int) round($thickness));
        if ($thickness === 0 && !$fill) {
            return $this;
        }
        $width = $size->getWidth();
        $height = $size->getHeight();

        $x1 = (int)round($center->getX() + $width / 2 * cos(deg2rad($start)));
        $y1 = (int)round($center->getY() + $height / 2 * sin(deg2rad($start)));
        $x2 = (int)round($center->getX() + $width / 2 * cos(deg2rad($end)));
        $y2 = (int)round($center->getY() + $height / 2 * sin(deg2rad($end)));

        if ($fill) {
            $this->chord($center, $size, $start, $end, $color, true, $thickness);
            $this->polygon(
                array(
                    $center,
                    new Point($x1, $y1),
                    new Point($x2, $y2),
                ),
                $color,
                true,
                $thickness
            );
        } else {
            $this->arc($center, $size, $start, $end, $color, $thickness);
            $this->line($center, new Point($x1, $y1), $color, $thickness);
            $this->line($center, new Point($x2, $y2), $color, $thickness);
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
            $point = new \GmagickDraw();

            $point->setfillcolor($pixel);
            $point->point($x, $y);

            $this->resource->drawimage($point);

            $pixel = null;
            $point = null;
        } catch (\GmagickException $e) {
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
            $rectangle = new \GmagickDraw();

            $rectangle->setstrokecolor($pixel);
            $rectangle->setstrokewidth($thickness);

            if ($fill) {
                $rectangle->setfillcolor($pixel);
            } else {
                $rectangle->setfillcolor('transparent');
            }
            $rectangle->rectangle($minX, $minY, $maxX, $maxY);
            $this->resource->drawImage($rectangle);
        } catch (\GmagickException $e) {
            throw new RuntimeException('Draw polygon operation failed', $e->getCode(), $e);
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
            $polygon = new \GmagickDraw();

            $polygon->setstrokecolor($pixel);
            $polygon->setstrokewidth($thickness);

            if ($fill) {
                $polygon->setfillcolor($pixel);
            } else {
                $polygon->setfillcolor('transparent');
            }

            $polygon->polygon($points);

            $this->resource->drawImage($polygon);

            unset($pixel, $polygon);
        } catch (\GmagickException $e) {
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
            $text = new \GmagickDraw();

            $text->setfont($font->getFile());
            /*
             * @see http://www.php.net/manual/en/imagick.queryfontmetrics.php#101027
             *
             * ensure font resolution is the same as GD's hard-coded 96
             */
            $text->setfontsize((int) ($font->getSize() * (96 / 72)));
            $text->setfillcolor($pixel);

            if ($width !== 0) {
                $string = $font->wrapText($string, $width, $angle);
            }

            $info = $this->resource->queryfontmetrics($text, $string);
            $rad = deg2rad($angle);
            $cos = cos($rad);
            $sin = sin($rad);

            $x1 = round(0 * $cos - 0 * $sin);
            $x2 = round($info['textWidth'] * $cos - $info['textHeight'] * $sin);
            $y1 = round(0 * $sin + 0 * $cos);
            $y2 = round($info['textWidth'] * $sin + $info['textHeight'] * $cos);

            $xdiff = 0 - min($x1, $x2);
            $ydiff = 0 - min($y1, $y2);

            $this->resource->annotateimage($text, $position->getX() + $x1 + $xdiff, $position->getY() + $y2 + $ydiff, $angle, $string);

            unset($pixel, $text);
        } catch (\GmagickException $e) {
            throw new RuntimeException('Draw text operation failed', $e->getCode(), $e);
        }

        return $this;
    }

    public function char(string|int $code, FontInterface $font, PointInterface $position, int|float $angle = 0) {
        return $this->text(is_int($code) ? mb_chr($code, 'UTF-8') : $code, $font, $position, $angle, 0);
    }

    public function fontSize(string $string, FontInterface $font, int|float $angle = 0)
    {
        $text = new \GmagickDraw();

        $text->setfont($font->getFile());
        /*
         * @see http://www.php.net/manual/en/imagick.queryfontmetrics.php#101027
         *
         * ensure font resolution is the same as GD's hard-coded 96
         */
        $text->setfontsize((int) ($font->getSize() * (96 / 72)));
        $text->setfontstyle(\Gmagick::STYLE_OBLIQUE);

        $info = $this->resource->queryfontmetrics($text, $string);
        return new Box($info['textWidth'], $info['textHeight']);
    }

    /**
     * {@inheritdoc}
     *
     */
    public function gamma(float $correction)
    {
        try {
            $this->resource->gammaimage($correction);
        } catch (\GmagickException $e) {
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
        if (!method_exists($this->resource, 'negateimage')) {
            throw new Exception('Gmagick version 1.1.0 RC3 is required for negative effect');
        }

        try {
            $this->resource->negateimage(false, \Gmagick::CHANNEL_ALL);
        } catch (\GmagickException $e) {
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
            $this->resource->setImageType(2);
        } catch (\GmagickException $e) {
            throw new RuntimeException('Failed to grayscale the image', $e->getCode(), $e);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     */
    public function colorize($color)
    {
        throw new Exception('Gmagick does not support colorize');
    }

    /**
     * {@inheritdoc}
     *
     */
    public function sharpen()
    {
        throw new Exception('Gmagick does not support sharpen yet');
    }

    /**
     * {@inheritdoc}
     *
     */
    public function blur(float $sigma = 1)
    {
        try {
            $this->resource->blurImage(0, $sigma);
        } catch (\GmagickException $e) {
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
            // This *emulates* setting the brightness
            $sign = $brightness < 0 ? -1 : 1;
            $v = abs($brightness) / 100;
            if ($sign > 0) {
                $v = (2 / (sin(($v * .99999 * M_PI_2) + M_PI_2))) - 2;
            }
            $this->resource->modulateimage(100 + $sign * $v * 100, 100, 100);
        } catch (\GmagickException $e) {
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
        if (!method_exists($this->resource, 'convolveimage')) {
            // convolveimage has been added in gmagick 2.0.1RC2
            throw new Exception('The version of Gmagick extension is too old: it does not support convolve.');
        }
        if ($matrix->getWidth() !== 3 || $matrix->getHeight() !== 3) {
            throw new InvalidArgumentException(sprintf('A convolution matrix must be 3x3 (%dx%d provided).', $matrix->getWidth(), $matrix->getHeight()));
        }
        try {
            $this->resource->convolveimage($matrix->getValueList());
        } catch (\ImagickException $e) {
            throw new RuntimeException('Failed to convolve the image');
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     */
    public function strip()
    {
        try {
            try {
                // $this->profile($this->palette->profile());
            } catch (\Exception $e) {
                // here we discard setting the profile as the previous incorporated profile
                // is corrupted, let's now strip the image
            }
            $this->resource->stripimage();
        } catch (\GmagickException $e) {
            throw new RuntimeException('Strip operation failed', $e->getCode(), $e);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     */
    public function paste(ImageAdapter $image, PointInterface $start, int|float $alpha = 100)
    {
        if (!$image instanceof self) {
            throw new InvalidArgumentException(sprintf('Gmagick\Image can only paste() Gmagick\Image instances, %s given', get_class($image)));
        }

        $alpha = (int) round($alpha);
        if ($alpha < 0 || $alpha > 100) {
            throw new InvalidArgumentException(sprintf('The %1$s argument can range from %2$d to %3$d, but you specified %4$d.', '$alpha', 0, 100, $alpha));
        }

        if ($alpha === 100) {
            try {
                $this->resource->compositeimage($image->resource, \Gmagick::COMPOSITE_DEFAULT, $start->getX(), $start->getY());
            } catch (\GmagickException $e) {
                throw new RuntimeException('Paste operation failed', $e->getCode(), $e);
            }
        } elseif ($alpha > 0) {
            throw new Exception('Gmagick doesn\'t support paste with alpha.', 1);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     */
    public function resize(BoxInterface $size, $filter = ImageAdapter::FILTER_UNDEFINED)
    {
        static $supportedFilters = array(

        );

        if (!array_key_exists($filter, $supportedFilters)) {
            throw new InvalidArgumentException('Unsupported filter type');
        }

        try {
            $this->resource->resizeimage($size->getWidth(), $size->getHeight(), $supportedFilters[$filter], 1);
        } catch (\GmagickException $e) {
            throw new RuntimeException('Resize operation failed', $e->getCode(), $e);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     */
    public function rotate(int|float $angle, mixed $background = null)
    {
        try {
            if ($background === null) {
                $background = '#fff';
            }
            $pixel = $this->converterToColor($background);

            $this->resource->rotateimage($pixel, $angle);

            unset($pixel);
        } catch (\GmagickException $e) {
            throw new RuntimeException('Rotate operation failed', $e->getCode(), $e);
        }

        return $this;
    }

    /**
     * Applies options before save or output.
     *
     * @param \Gmagick $image
     * @param array $options
     * @param string $path
     *
     */
    private function applyImageOptions(\Gmagick $image, array $options, $path)
    {
        if (isset($options['format'])) {
            $format = $options['format'];
        } elseif ('' !== $extension = pathinfo($path, \PATHINFO_EXTENSION)) {
            $format = $extension;
        } else {
            $format = pathinfo($image->getImageFilename(), \PATHINFO_EXTENSION);
        }

        $format = strtolower($format);

        switch ($format) {
            case 'jpeg':
            case 'jpg':
            case 'pjpeg':
                if (!isset($options['jpeg_quality'])) {
                    if (isset($options['quality'])) {
                        $options['jpeg_quality'] = $options['quality'];
                    }
                }
                if (isset($options['jpeg_quality'])) {
                    $image->setCompressionQuality($options['jpeg_quality']);
                }
                if (isset($options['jpeg_sampling_factors'])) {
                    if (!is_array($options['jpeg_sampling_factors']) || \count($options['jpeg_sampling_factors']) < 1) {
                        throw new InvalidArgumentException('jpeg_sampling_factors option should be an array of integers');
                    }
                    $image->setSamplingFactors(array_map(function ($factor) {
                        return (int) $factor;
                    }, $options['jpeg_sampling_factors']));
                }
                break;
            case 'png':
                if (!isset($options['png_compression_level'])) {
                    if (isset($options['quality'])) {
                        $options['png_compression_level'] = round((100 - $options['quality']) * 9 / 100);
                    }
                }
                if (isset($options['png_compression_level'])) {
                    if ($options['png_compression_level'] < 0 || $options['png_compression_level'] > 9) {
                        throw new InvalidArgumentException('png_compression_level option should be an integer from 0 to 9');
                    }
                }
                if (isset($options['png_compression_filter'])) {
                    if ($options['png_compression_filter'] < 0 || $options['png_compression_filter'] > 9) {
                        throw new InvalidArgumentException('png_compression_filter option should be an integer from 0 to 9');
                    }
                }
                if (isset($options['png_compression_level']) || isset($options['png_compression_filter'])) {
                    // first digit: compression level (default: 7)
                    $compression = isset($options['png_compression_level']) ? $options['png_compression_level'] * 10 : 70;
                    // second digit: compression filter (default: 5)
                    $compression += $options['png_compression_filter'] ?? 5;
                    $image->setCompressionQuality($compression);
                }
                break;
            case 'webp':
                if (!isset($options['webp_quality'])) {
                    if (isset($options['quality'])) {
                        $options['webp_quality'] = $options['quality'];
                    }
                }
                if (isset($options['webp_quality'])) {
                    $image->setCompressionQuality($options['webp_quality']);
                }
                break;
        }
        if (isset($options['resolution-units']) && isset($options['resolution-x']) && isset($options['resolution-y'])) {
            switch ($options['resolution-units']) {
                case ImageAdapter::RESOLUTION_PIXELSPERCENTIMETER:
                    $image->setimageunits(\Gmagick::RESOLUTION_PIXELSPERCENTIMETER);
                    break;
                case ImageAdapter::RESOLUTION_PIXELSPERINCH:
                    $image->setimageunits(\Gmagick::RESOLUTION_PIXELSPERINCH);
                    break;
                default:
                    throw new InvalidArgumentException('Unsupported image unit format');
            }
            $image->setimageresolution($options['resolution-x'], $options['resolution-y']);
        }
    }

    /**
     * {@inheritdoc}
     *
     */
    public function save()
    {
        $path = $this->file;

        if ('' === trim($path)) {
            throw new RuntimeException('You can omit save path only if image has been open from a file');
        }

        try {
            $this->resource->writeimage($path, true);
        } catch (\GmagickException $e) {
            throw new RuntimeException('Save operation failed', $e->getCode(), $e);
        }

        return $this;
    }


    /**
     * Destroys allocated gmagick resources.
     */
    public function __destruct()
    {
        if ($this->resource instanceof \Gmagick) {
            $this->resource->clear();
            $this->resource->destroy();
        }
    }

    /**
     * {@inheritdoc}
     *
     */
    public function __clone()
    {
        $this->resource = clone $this->resource;
    }

    /**
     * Gets specifically formatted color string from Color instance.
     *
     * @return \GmagickPixel
     */
    public function converterToColor(mixed $color): GmagickPixel
    {
//        if () {
//            throw new InvalidArgumentException('Gmagick doesn\'t support transparency');
//        }

        return new \GmagickPixel((string) $color);
    }

    public function getHeight(): int
    {
        return $this->resource->getimageheight();
    }

    public function getWidth(): int
    {
        return $this->resource->getimagewidth();
    }

    public function scale(BoxInterface $box)
    {
        $this->resource->scaleimage($box->getWidth(), $box->getHeight());
        return $this;
    }

    public function getColorAt(PointInterface $point)
    {
    }

    public function copy()
    {
        // TODO: Implement copy() method.
    }

    public function crop(PointInterface $start, BoxInterface $size)
    {
        $this->resource->cropimage($size->getWidth(), $size->getHeight(), $start->getX(), $start->getY());
        return $this;
    }

    public function saveAs(mixed $output = null, string $type = ''): bool
    {
        $this->resource->writeimage($output, true);
        return true;
    }

    public function fill($fill)
    {
        // TODO: Implement fill() method.
    }

    public function pastePart(ImageAdapter $src, PointInterface $srcStart, BoxInterface $srcBox, PointInterface $start, BoxInterface|null $box = null, int|float $alpha = 100)
    {
        // TODO: Implement pastePart() method.
    }

    public function transparent(mixed $color)
    {
        throw new Exception('gmagick is not support transparent');
    }

    public function converterFromColor(mixed $color): mixed
    {
        if (is_array($color)) {
            return $color;
        }
        if (!$color instanceof \GmagickPixel) {
            $color = new \GmagickPixel((string)$color);
        }
        $res = $color->getColor(true);
        return [$res['r'], $res['g'], $res['b'], $res['a']];
    }

    public function thumbnail(BoxInterface $box)
    {
        $this->resource->thumbnailimage($box->getWidth(), $box->getHeight());
        return $this;
    }

    protected function addFont(\GmagickDraw $draw, int|string $fontName) {
        if (is_file($fontName)) {
            $draw->setFont($fontName);
        } else {
            $draw->setFont($this->converterFont($fontName));
        }
    }

    protected function converterFont(int|string $fontName): string {
        if (!is_numeric($fontName)) {
            return $fontName;
        }
        $fontItems = $this->resource->queryFonts();
        $i = intval($fontName);
        if (isset($fontItems[$i])) {
            return $fontItems[$i];
        }
        throw new \Exception('Font is error');
    }

    private function getFilter(string $filter): int {
        return match ($filter) {
            ImageAdapter::FILTER_UNDEFINED => \Gmagick::FILTER_UNDEFINED,
            ImageAdapter::FILTER_BESSEL => \Gmagick::FILTER_BESSEL,
            ImageAdapter::FILTER_BLACKMAN => \Gmagick::FILTER_BLACKMAN,
            ImageAdapter::FILTER_BOX => \Gmagick::FILTER_BOX,
            ImageAdapter::FILTER_CATROM => \Gmagick::FILTER_CATROM,
            ImageAdapter::FILTER_CUBIC => \Gmagick::FILTER_CUBIC,
            ImageAdapter::FILTER_GAUSSIAN => \Gmagick::FILTER_GAUSSIAN,
            ImageAdapter::FILTER_HANNING => \Gmagick::FILTER_HANNING,
            ImageAdapter::FILTER_HAMMING => \Gmagick::FILTER_HAMMING,
            ImageAdapter::FILTER_HERMITE => \Gmagick::FILTER_HERMITE,
            ImageAdapter::FILTER_LANCZOS => \Gmagick::FILTER_LANCZOS,
            ImageAdapter::FILTER_MITCHELL => \Gmagick::FILTER_MITCHELL,
            ImageAdapter::FILTER_POINT => \Gmagick::FILTER_POINT,
            ImageAdapter::FILTER_QUADRATIC => \Gmagick::FILTER_QUADRATIC,
            ImageAdapter::FILTER_SINC => \Gmagick::FILTER_SINC,
            ImageAdapter::FILTER_TRIANGLE => \Gmagick::FILTER_TRIANGLE,
            default => throw new \Exception('error filter'),
        };
    }
}