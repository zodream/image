<?php
namespace Zodream\Image\Adapters;

use Zodream\Image\Base\BoxInterface;
use Zodream\Image\Base\FontInterface;
use Zodream\Image\Base\Matrix;
use Zodream\Image\Base\PointInterface;

interface ImageAdapter {

    /**
     * The original image is scaled so it is fully contained within the thumbnail dimensions (the image width/height ratio doesn't change).
     *
     * @var int
     */
    const THUMBNAIL_INSET = 0x00000001;

    /**
     * The thumbnail is scaled so that its smallest side equals the length of the corresponding side in the original image (the width or the height are cropped).
     *
     * @var int
     */
    const THUMBNAIL_OUTBOUND = 0x00000002;

    /**
     * Allow upscaling the image if it's smaller than the wanted thumbnail size.
     *
     * @var int
     */
    const THUMBNAIL_FLAG_UPSCALE = 0x00010000;

    /**
     * Instead of creating a new image instance, the thumbnail method modifies the original image (saving memory.
     *
     * @var int
     */
    const THUMBNAIL_FLAG_NOCLONE = 0x00020000;

    /**
     * Resolution units: pixels per inch.
     *
     * @var string
     */
    const RESOLUTION_PIXELSPERINCH = 'ppi';

    /**
     * Resolution units: pixels per centimeter.
     *
     * @var string
     */
    const RESOLUTION_PIXELSPERCENTIMETER = 'ppc';

    /**
     * Image interlacing: none.
     *
     * @var string
     */
    const INTERLACE_NONE = 'none';

    /**
     * Image interlacing: scanline.
     *
     * @var string
     */
    const INTERLACE_LINE = 'line';

    /**
     * Image interlacing: plane.
     *
     * @var string
     */
    const INTERLACE_PLANE = 'plane';

    /**
     * Image interlacing: like plane interlacing except the different planes are saved to individual files.
     *
     * @var string
     */
    const INTERLACE_PARTITION = 'partition';

    /**
     * Image filter: none/undefined.
     *
     * @var string
     */
    const FILTER_UNDEFINED = 'undefined';

    /**
     * Resampling filter: point (interpolated).
     *
     * @var string
     */
    const FILTER_POINT = 'point';

    /**
     * Resampling filter: box.
     *
     * @var string
     */
    const FILTER_BOX = 'box';

    /**
     * Resampling filter: triangle.
     *
     * @var string
     */
    const FILTER_TRIANGLE = 'triangle';

    /**
     * Resampling filter: hermite.
     *
     * @var string
     */
    const FILTER_HERMITE = 'hermite';

    /**
     * Resampling filter: hanning.
     *
     * @var string
     */
    const FILTER_HANNING = 'hanning';

    /**
     * Resampling filter: hamming.
     *
     * @var string
     */
    const FILTER_HAMMING = 'hamming';

    /**
     * Resampling filter: blackman.
     *
     * @var string
     */
    const FILTER_BLACKMAN = 'blackman';

    /**
     * Resampling filter: gaussian.
     *
     * @var string
     */
    const FILTER_GAUSSIAN = 'gaussian';

    /**
     * Resampling filter: quadratic.
     *
     * @var string
     */
    const FILTER_QUADRATIC = 'quadratic';

    /**
     * Resampling filter: cubic.
     *
     * @var string
     */
    const FILTER_CUBIC = 'cubic';

    /**
     * Resampling filter: catrom.
     *
     * @var string
     */
    const FILTER_CATROM = 'catrom';

    /**
     * Resampling filter: mitchell.
     *
     * @var string
     */
    const FILTER_MITCHELL = 'mitchell';

    /**
     * Resampling filter: lanczos.
     *
     * @var string
     */
    const FILTER_LANCZOS = 'lanczos';

    /**
     * Resampling filter: bessel.
     *
     * @var string
     */
    const FILTER_BESSEL = 'bessel';

    /**
     * Resampling filter: sinc.
     *
     * @var string
     */
    const FILTER_SINC = 'sinc';

    public function create(BoxInterface $size, $color = null);

    public function open($path);

    public function load($string);

    public function read($resource);

    public function getHeight();

    public function getWidth();

    public function scale(BoxInterface $box);

    public function getColorAt(PointInterface $point);

    public function copy();

    public function crop(PointInterface $start, BoxInterface $size);

    public function resize(BoxInterface $size, $filter = ImageAdapter::FILTER_UNDEFINED);

    public function rotate($angle, $background = null);

    public function paste(ImageAdapter $image, PointInterface  $start, $alpha = 100);

    public function save();

    public function saveAs($output = null, $type = '');

    public function fill($fill);


    public function arc(PointInterface $center, BoxInterface  $size, $start, $end, $color, $thickness = 1);

    public function chord(PointInterface $center, BoxInterface  $size, $start, $end, $color, $fill = false, $thickness = 1);

    public function circle(PointInterface $center, $radius, $color, $fill = false, $thickness = 1);

    public function ellipse(PointInterface $center, BoxInterface  $size, $color, $fill = false, $thickness = 1);

    public function line(PointInterface $start, PointInterface $end, $outline, $thickness = 1);

    public function pieSlice(PointInterface $center, BoxInterface  $size, $start, $end, $color, $fill = false, $thickness = 1);

    public function dot(PointInterface $position, $color);

    public function rectangle(PointInterface $leftTop, PointInterface $rightBottom, $color, $fill = false, $thickness = 1);

    public function polygon(array $coordinates, $color, $fill = false, $thickness = 1);

    public function text($string, FontInterface $font, PointInterface $position, $angle = 0, $width = null);

    public function fontSize($string, FontInterface $font, $angle = 0);

    public function gamma($correction);

    public function negative();

    public function grayscale();

    public function colorize($color);

    public function sharpen();

    public function blur($sigma);

    public function brightness($brightness);

    public function convolve(Matrix $matrix);
}