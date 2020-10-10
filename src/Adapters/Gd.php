<?php
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

class Gd implements ImageAdapter {

    const ALLOW_TYPES = array(
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
    protected $resource;

    public function create(BoxInterface $size, $color = null)
    {
        $width = $size->getWidth();
        $height = $size->getHeight();

        $resource = imagecreatetruecolor($width, $height);

        if (false === $resource) {
            throw new RuntimeException('Create operation failed');
        }

        if (empty($color)) {
            $color = '#fff';
        }
        $this->fill($color);

//        $index = imagecolorallocatealpha($resource, $color->getRed(), $color->getGreen(), $color->getBlue(), round(127 * (100 - $color->getAlpha()) / 100));
//
//        if (false === $index) {
//            throw new RuntimeException('Unable to allocate color');
//        }
//
//        if (false === imagefill($resource, 0, 0, $index)) {
//            throw new RuntimeException('Could not set background color fill');
//        }
//
//        if ($color->getAlpha() <= 5) {
//            imagecolortransparent($resource, $index);
//        }
        $this->resource = $resource;
        $this->height = $height;
        $this->width = $width;
        return $this;
    }

    public function open($file) {
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

    public function load($string) {
        $this->resource = imagecreatefromstring($string);
        $this->height = imagesy($this->resource);
        $this->width = imagesx($this->resource);
        return $this;
    }

    public function read($resource) {
        $this->resource = $resource;
        $this->height = imagesy($this->resource);
        $this->width = imagesx($this->resource);
        return $this;
    }

    final public function crop(PointInterface $start, BoxInterface $size)
    {
        if (!$start->in($this->getSize())) {
            throw new OutOfBoundsException('Crop coordinates must start at minimum 0, 0 position from top left corner, crop height and width must be positive integers and must not exceed the current image borders');
        }

        $width = $size->getWidth();
        $height = $size->getHeight();

        $dest = $this->createImage($size, 'crop');

        if (false === imagecopy($dest, $this->resource, 0, 0, $start->getX(), $start->getY(), $width, $height)) {
            imagedestroy($dest);
            throw new RuntimeException('Image crop operation failed');
        }

        imagedestroy($this->resource);

        $this->resource = $dest;

        return $this;
    }

    final public function paste(ImageAdapter $image, PointInterface $start, $alpha = 100)
    {
        if (!$image instanceof self) {
            throw new InvalidArgumentException(sprintf('Gd\Image can only paste() Gd\Image instances, %s given', get_class($image)));
        }

        $alpha = (int) round($alpha);
        if ($alpha < 0 || $alpha > 100) {
            throw new InvalidArgumentException(sprintf('The %1$s argument can range from %2$d to %3$d, but you specified %4$d.', '$alpha', 0, 100, $alpha));
        }

        $size = $image->getSize();

        if ($alpha === 100) {
            imagealphablending($this->resource, true);
            imagealphablending($image->resource, true);

            $success = imagecopy($this->resource, $image->resource, $start->getX(), $start->getY(), 0, 0, $size->getWidth(), $size->getHeight());

            imagealphablending($this->resource, false);
            imagealphablending($image->resource, false);

            if ($success === false) {
                throw new RuntimeException('Image paste operation failed');
            }
        } elseif ($alpha > 0) {
            if (false === imagecopymerge(/*dst_im*/$this->resource, /*src_im*/$image->resource, /*dst_x*/$start->getX(), /*dst_y*/$start->getY(), /*src_x*/0, /*src_y*/0, /*src_w*/$size->getWidth(), /*src_h*/$size->getHeight(), /*pct*/$alpha)) {
                throw new RuntimeException('Image paste operation failed');
            }
        }

        return $this;
    }

    final public function resize(BoxInterface $size, $filter = ImageAdapter::FILTER_UNDEFINED)
    {
        if (ImageAdapter::FILTER_UNDEFINED !== $filter) {
            throw new InvalidArgumentException('Unsupported filter type, GD only supports ImageInterface::FILTER_UNDEFINED filter');
        }

        $width = $size->getWidth();
        $height = $size->getHeight();

        $dest = $this->createImage($size, 'resize');

        imagealphablending($this->resource, true);
        imagealphablending($dest, true);

        $success = imagecopyresampled($dest, $this->resource, 0, 0, 0, 0, $width, $height, imagesx($this->resource), imagesy($this->resource));

        imagealphablending($this->resource, false);
        imagealphablending($dest, false);

        if ($success === false) {
            imagedestroy($dest);
            throw new RuntimeException('Image resize operation failed');
        }

        imagedestroy($this->resource);

        $this->resource = $dest;

        return $this;
    }

    final public function rotate($angle, $background = null)
    {
        if ($background === null) {
            $background = '#fff';
        }
        $color = $this->getColor($background);
        $resource = imagerotate($this->resource, -1 * $angle, $color);

        if (false === $resource) {
            throw new RuntimeException('Image rotate operation failed');
        }

        imagedestroy($this->resource);
        $this->resource = $resource;

        return $this;
    }

    public function scale(BoxInterface $box) {
        $resource = imagecreatetruecolor($box->getWidth(), $box->getHeight());
        $size = $this->getSize();
        imagecopyresampled($resource, $this->resource, 0, 0, 0, 0,
            $box->getWidth(), $box->getHeight(), $size->getWidth(), $size->getHeight());
        $this->close();
        $this->read($resource);
        return $this;
    }

    public function copy() {
        return clone $this;
    }

    public function fill($fill) {
        $size = $this->getSize();

        if (is_string($fill)) {
            imagefilledrectangle(
                $this->resource,
                0,
                $size->getHeight(),
                $size->getWidth(),
                0,
                $this->getColor($fill)
            );
            return $this;
        }
        if (!$fill instanceof ImageAdapter) {
            throw new RuntimeException('Fill operation failed');
        }
        for ($x = 0, $width = $size->getWidth(); $x < $width; $x++) {
            for ($y = 0, $height = $size->getHeight(); $y < $height; $y++) {
                if (false === imagesetpixel($this->resource, $x, $y,
                        $fill->getColorAt(new Point($x, $y)))) {
                    throw new RuntimeException('Fill operation failed');
                }
            }
        }
        return $this;
    }


    public function arc(PointInterface $center, BoxInterface $size, $start, $end, $color, $thickness = 1)
    {
        $thickness = max(0, (int) round($thickness));
        if ($thickness === 0) {
            return $this;
        }
        imagesetthickness($this->resource, $thickness);

        if (false === imagealphablending($this->resource, true)) {
            throw new RuntimeException('Draw arc operation failed');
        }

        if (false === imagearc($this->resource, $center->getX(), $center->getY(), $size->getWidth(), $size->getHeight(), $start, $end, $this->getColor($color))) {
            imagealphablending($this->resource, false);
            throw new RuntimeException('Draw arc operation failed');
        }

        if (false === imagealphablending($this->resource, false)) {
            throw new RuntimeException('Draw arc operation failed');
        }

        return $this;
    }

    public function chord(PointInterface $center, BoxInterface $size, $start, $end, $color, $fill = false, $thickness = 1)
    {
        $thickness = max(0, (int) round($thickness));
        if ($thickness === 0 && !$fill) {
            return $this;
        }
        imagesetthickness($this->resource, $thickness);

        if (false === imagealphablending($this->resource, true)) {
            throw new RuntimeException('Draw chord operation failed');
        }

        if ($fill) {
            $style = IMG_ARC_CHORD;
            if (false === imagefilledarc($this->resource, $center->getX(), $center->getY(), $size->getWidth(), $size->getHeight(), $start, $end, $this->getColor($color), $style)) {
                imagealphablending($this->resource, false);
                throw new RuntimeException('Draw chord operation failed');
            }
        } else {
            foreach (array(IMG_ARC_NOFILL, IMG_ARC_NOFILL | IMG_ARC_CHORD) as $style) {
                if (false === imagefilledarc($this->resource, $center->getX(), $center->getY(), $size->getWidth(), $size->getHeight(), $start, $end, $this->getColor($color), $style)) {
                    imagealphablending($this->resource, false);
                    throw new RuntimeException('Draw chord operation failed');
                }
            }
        }

        if (false === imagealphablending($this->resource, false)) {
            throw new RuntimeException('Draw chord operation failed');
        }

        return $this;
    }

    public function circle(PointInterface $center, $radius, $color, $fill = false, $thickness = 1)
    {
        $diameter = $radius * 2;

        return $this->ellipse($center, new Box($diameter, $diameter), $color, $fill, $thickness);
    }

    public function ellipse(PointInterface $center, BoxInterface $size, $color, $fill = false, $thickness = 1)
    {
        $thickness = max(0, (int) round($thickness));
        if ($thickness === 0 && !$fill) {
            return $this;
        }
        if (function_exists('imageantialias')) {
            imageantialias($this->resource, true);
        }
        imagesetthickness($this->resource, $thickness);

        if ($fill) {
            $callback = 'imagefilledellipse';
        } else {
            $callback = 'imageellipse';
        }

        if (function_exists('imageantialias')) {
            imageantialias($this->resource, true);
        }
        if (false === imagealphablending($this->resource, true)) {
            throw new RuntimeException('Draw ellipse operation failed');
        }

        if (function_exists('imageantialias')) {
            imageantialias($this->resource, true);
        }
        if (false === $callback($this->resource, $center->getX(), $center->getY(), $size->getWidth(), $size->getHeight(), $this->getColor($color))) {
            imagealphablending($this->resource, false);
            throw new RuntimeException('Draw ellipse operation failed');
        }

        if (false === imagealphablending($this->resource, false)) {
            throw new RuntimeException('Draw ellipse operation failed');
        }

        return $this;
    }

    public function line(PointInterface $start, PointInterface $end, $color, $thickness = 1)
    {
        $thickness = max(0, (int) round($thickness));
        if ($thickness === 0) {
            return $this;
        }
        imagesetthickness($this->resource, $thickness);

        if (false === imagealphablending($this->resource, true)) {
            throw new RuntimeException('Draw line operation failed');
        }

        if (false === imageline($this->resource, $start->getX(), $start->getY(), $end->getX(), $end->getY(), $this->getColor($color))) {
            imagealphablending($this->resource, false);
            throw new RuntimeException('Draw line operation failed');
        }

        if (false === imagealphablending($this->resource, false)) {
            throw new RuntimeException('Draw line operation failed');
        }

        return $this;
    }

    public function pieSlice(PointInterface $center, BoxInterface $size, $start, $end, $color, $fill = false, $thickness = 1)
    {
        $thickness = max(0, (int) round($thickness));
        if ($thickness === 0 && !$fill) {
            return $this;
        }
        imagesetthickness($this->resource, $thickness);

        if ($fill) {
            $style = IMG_ARC_EDGED;
        } else {
            $style = IMG_ARC_EDGED | IMG_ARC_NOFILL;
        }

        if (false === imagealphablending($this->resource, true)) {
            throw new RuntimeException('Draw chord operation failed');
        }

        if (false === imagefilledarc($this->resource, $center->getX(), $center->getY(), $size->getWidth(), $size->getHeight(), $start, $end, $this->getColor($color), $style)) {
            imagealphablending($this->resource, false);
            throw new RuntimeException('Draw chord operation failed');
        }

        if (false === imagealphablending($this->resource, false)) {
            throw new RuntimeException('Draw chord operation failed');
        }

        return $this;
    }

    public function dot(PointInterface $position, $color)
    {
        if (false === imagealphablending($this->resource, true)) {
            throw new RuntimeException('Draw point operation failed');
        }

        if (false === imagesetpixel($this->resource, $position->getX(), $position->getY(), $this->getColor($color))) {
            imagealphablending($this->resource, false);
            throw new RuntimeException('Draw point operation failed');
        }

        if (false === imagealphablending($this->resource, false)) {
            throw new RuntimeException('Draw point operation failed');
        }

        return $this;
    }

    public function rectangle(PointInterface $leftTop, PointInterface $rightBottom, $color, $fill = false, $thickness = 1)
    {
        $thickness = max(0, (int) round($thickness));
        if ($thickness === 0 && !$fill) {
            return $this;
        }
        imagesetthickness($this->resource, $thickness);

        $minX = min($leftTop->getX(), $rightBottom->getX());
        $maxX = max($leftTop->getX(), $rightBottom->getX());
        $minY = min($leftTop->getY(), $rightBottom->getY());
        $maxY = max($leftTop->getY(), $rightBottom->getY());

        if ($fill) {
            $callback = 'imagefilledrectangle';
        } else {
            $callback = 'imagerectangle';
        }

        if (false === imagealphablending($this->resource, true)) {
            throw new RuntimeException('Draw polygon operation failed');
        }

        if (false === $callback($this->resource, $minX, $minY, $maxX, $maxY, $this->getColor($color))) {
            imagealphablending($this->resource, false);
            throw new RuntimeException('Draw polygon operation failed');
        }

        if (false === imagealphablending($this->resource, false)) {
            throw new RuntimeException('Draw polygon operation failed');
        }

        return $this;
    }

    public function polygon(array $coordinates, $color, $fill = false, $thickness = 1)
    {
        $thickness = max(0, (int) round($thickness));
        if ($thickness === 0 && !$fill) {
            return $this;
        }
        imagesetthickness($this->resource, $thickness);

        if (count($coordinates) < 3) {
            throw new InvalidArgumentException(sprintf('A polygon must consist of at least 3 points, %d given', count($coordinates)));
        }

        $points = call_user_func_array('array_merge', array_map(function (PointInterface $p) {
            return array($p->getX(), $p->getY());
        }, $coordinates));

        if ($fill) {
            $callback = 'imagefilledpolygon';
        } else {
            $callback = 'imagepolygon';
        }

        if (false === imagealphablending($this->resource, true)) {
            throw new RuntimeException('Draw polygon operation failed');
        }

        if (false === $callback($this->resource, $points, count($coordinates), $this->getColor($color))) {
            imagealphablending($this->resource, false);
            throw new RuntimeException('Draw polygon operation failed');
        }

        if (false === imagealphablending($this->resource, false)) {
            throw new RuntimeException('Draw polygon operation failed');
        }

        return $this;
    }

    public function text($string, FontInterface $font, PointInterface $position, $angle = 0, $width = null)
    {
        $angle = -1 * $angle;
        $fontsize = $font->getSize();
        $fontfile = $font->getFile();
        $x = $position->getX();
        $y = $position->getY() + $fontsize;

        if ($width !== null) {
            $string = $font->wrapText($string, $width, $angle);
        }

        if (false === imagealphablending($this->resource, true)) {
            throw new RuntimeException('Font mask operation failed');
        }

        if ($fontfile && DIRECTORY_SEPARATOR === '\\') {
            // On Windows imagefttext() throws a "Could not find/open font" error if $fontfile is not an absolute path.
            $fontfileRealpath = realpath($fontfile);
            if ($fontfileRealpath !== false) {
                $fontfile = $fontfileRealpath;
            }
        }
        if (false === imagefttext($this->resource, $fontsize, $angle, $x, $y, $this->getColor($font->getColor()), $fontfile, $string)) {
            imagealphablending($this->resource, false);
            throw new RuntimeException('Font mask operation failed');
        }

        if (false === imagealphablending($this->resource, false)) {
            throw new RuntimeException('Font mask operation failed');
        }

        return $this;
    }

    public function fontSize($string, FontInterface $font, $angle = 0) {
        $box = imagettfbbox($font->getSize(), $angle, $font->getFile(), $string);
        return new Box(abs($box[4] - $box[0]), abs($box[5] - $box[1]));
    }


    public function gamma($correction)
    {
        if (false === imagegammacorrect($this->resource, 1.0, $correction)) {
            throw new RuntimeException('Failed to apply gamma correction to the image');
        }

        return $this;
    }

    public function negative()
    {
        if (false === imagefilter($this->resource, IMG_FILTER_NEGATE)) {
            throw new RuntimeException('Failed to negate the image');
        }

        return $this;
    }

    public function grayscale()
    {
        if (false === imagefilter($this->resource, IMG_FILTER_GRAYSCALE)) {
            throw new RuntimeException('Failed to grayscale the image');
        }

        return $this;
    }

    public function colorize($color)
    {
        $color = Colors::converter(...func_get_args());
        if (false === imagefilter($this->resource, IMG_FILTER_COLORIZE, $color[0], $color[1], $color[2])) {
            throw new RuntimeException('Failed to colorize the image');
        }
        return $this;
    }

    public function sharpen()
    {
        $sharpenMatrix = array(array(-1, -1, -1), array(-1, 16, -1), array(-1, -1, -1));
        $divisor = array_sum(array_map('array_sum', $sharpenMatrix));

        if (false === imageconvolution($this->resource, $sharpenMatrix, $divisor, 0)) {
            throw new RuntimeException('Failed to sharpen the image');
        }

        return $this;
    }

    public function blur($sigma = 1)
    {
        if (false === imagefilter($this->resource, IMG_FILTER_GAUSSIAN_BLUR)) {
            throw new RuntimeException('Failed to blur the image');
        }

        return $this;
    }

    public function brightness($brightness)
    {
        $gdBrightness = (int) round($brightness / 100 * 255);
        if ($gdBrightness < -255 || $gdBrightness > 255) {
            throw new InvalidArgumentException(sprintf('The %1$s argument can range from %2$d to %3$d, but you specified %4$d.', '$brightness', -100, 100, $brightness));
        }
        if (false === imagefilter($this->resource, IMG_FILTER_BRIGHTNESS, $gdBrightness)) {
            throw new RuntimeException('Failed to brightness the image');
        }

        return $this;
    }

    public function convolve(Matrix $matrix)
    {
        if ($matrix->getWidth() !== 3 || $matrix->getHeight() !== 3) {
            throw new InvalidArgumentException(sprintf('A convolution matrix must be 3x3 (%dx%d provided).', $matrix->getWidth(), $matrix->getHeight()));
        }
        if (false === imageconvolution($this->resource, $matrix->getMatrix(), 1, 0)) {
            throw new RuntimeException('Failed to convolve the image');
        }

        return $this;
    }

    public function getColor($color) {
        $color = Colors::converter(...func_get_args());
        if (is_integer($color)) {
            return $color;
        }
        return imagecolorallocate($this->resource, $color[0], $color[1], $color[2]);
    }

    public function getColorAt(PointInterface $point) {
        if (!$point->in($this->getSize())) {
            throw new RuntimeException(sprintf('Error getting color at point [%s,%s]. The point must be inside the image of size [%s,%s]', $point->getX(), $point->getY(), $this->getSize()->getWidth(), $this->getSize()->getHeight()));
        }

        return imagecolorat($this->resource, $point->getX(), $point->getY());
        //$info = imagecolorsforindex($this->resource, $index);

        //return $this->palette->color(array($info['red'], $info['green'], $info['blue']), max(min(100 - (int) round($info['alpha'] / 127 * 100), 100), 0));
    }

    public function save() {
        return $this->saveAs($this->file);
    }

    /**
     * 另存为
     * @param string|null $output 如果为null 表示输出
     * @param string $type
     * @return bool
     */
    public function saveAs($output = null, $type = '') {
        $this->setRealType($type);
        if (!is_null($output)) {
            $output = (string)$output;
        }
        return call_user_func('image'.$this->realType, $this->resource, $output);
    }

    public function setEmptyImage() {
        $this->resource = null;
        return $this;
    }

    public function getHeight() {
        return $this->height;
    }

    public function getWidth() {
        return $this->width;
    }

    public function getSize() {
        return new Box($this->getWidth(), $this->getHeight());
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
        foreach (self::ALLOW_TYPES as $key => $item) {
            if ((!is_array($item) && $item == $type)
                || (is_array($item) && in_array($type, $item))) {
                $this->realType = $type;
                return;
            }
        }
    }

    public function close() {
        if (is_resource($this->resource) && 'gd' === get_resource_type($this->resource)) {
            imagedestroy($this->resource);
        }
        $this->resource = null;
    }

    public function __destruct() {
        $this->close();
    }

    public function __clone() {
        $size = $this->getSize();
        $copy = $this->createImage($size, 'copy');
        if (false === imagecopy($copy, $this->resource, 0, 0, 0, 0, $size->getWidth(), $size->getHeight())) {
            imagedestroy($copy);
            throw new RuntimeException('Image copy operation failed');
        }
        $this->resource = $copy;
    }

    protected function check($file) {
        return is_file($file) && getimagesize($file) && extension_loaded('gd');
    }

    private function createImage(BoxInterface $size, $operation)
    {
        $resource = imagecreatetruecolor($size->getWidth(), $size->getHeight());

        if (false === $resource) {
            throw new RuntimeException('Image ' . $operation . ' failed');
        }

        if (false === imagealphablending($resource, false) || false === imagesavealpha($resource, true)) {
            throw new RuntimeException('Image ' . $operation . ' failed');
        }

        if (function_exists('imageantialias')) {
            imageantialias($resource, true);
        }

        $transparent = imagecolorallocatealpha($resource, 255, 255, 255, 127);
        imagefill($resource, 0, 0, $transparent);
        imagecolortransparent($resource, $transparent);

        return $resource;
    }
}