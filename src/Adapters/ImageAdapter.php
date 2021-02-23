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

    /**
     * @param BoxInterface $size
     * @param null $color
     * @return static
     */
    public function create(BoxInterface $size, $color = null);

    public function open($path);

    public function load($string);

    public function read($resource);

    public function getHeight();

    public function getWidth();

    public function getRealType();

    /**
     * @return BoxInterface
     */
    public function getSize();

    public function scale(BoxInterface $box);

    public function getColorAt(PointInterface $point);

    public function copy();

    public function crop(PointInterface $start, BoxInterface $size);

    public function resize(BoxInterface $size, $filter = ImageAdapter::FILTER_UNDEFINED);

    public function rotate($angle, $background = null);

    /**
     * 把一张图片放在什么位置上
     * @param ImageAdapter $image 要放的图片
     * @param PointInterface $start 放在那个位置
     * @param int $alpha 透明度
     * @return static
     */
    public function paste(ImageAdapter $image, PointInterface  $start, $alpha = 100);

    /**
     * 截取一部分图片放在什么位置
     * @param ImageAdapter $src 源图
     * @param PointInterface $srcStart 源图截取的位置
     * @param BoxInterface $srcBox 源图截取的大小
     * @param PointInterface $start 放在什么位置
     * @param BoxInterface|null $box 是否放大
     * @param int $alpha
     * @return static
     */
    public function pastePart(ImageAdapter $src,
                              PointInterface $srcStart,
                              BoxInterface $srcBox,
                              PointInterface $start,
                              BoxInterface $box = null, $alpha = 100);

    /**
     * 生成缩略图
     * @param BoxInterface $box
     * @return static
     */
    public function thumbnail(BoxInterface $box);

    public function save();

    public function saveAs($output = null, $type = '');

    public function fill($fill);

    /**
     * 画弧
     * @param PointInterface $center
     * @param BoxInterface $size
     * @param $start
     * @param $end
     * @param $color
     * @param int $thickness
     * @return mixed
     */
    public function arc(PointInterface $center, BoxInterface  $size, $start, $end, $color, $thickness = 1);

    /**
     * 画椭圆弧
     * @param PointInterface $center
     * @param BoxInterface $size
     * @param $start
     * @param $end
     * @param $color
     * @param false $fill
     * @param int $thickness
     * @return mixed
     */
    public function chord(PointInterface $center, BoxInterface  $size, $start, $end, $color, $fill = false, $thickness = 1);

    /**
     * 画圆
     * @param PointInterface $center
     * @param $radius
     * @param $color
     * @param false $fill
     * @param int $thickness
     * @return mixed
     */
    public function circle(PointInterface $center, $radius, $color, $fill = false, $thickness = 1);

    /**
     * 画椭圆
     * @param PointInterface $center
     * @param BoxInterface $size
     * @param $color
     * @param false $fill
     * @param int $thickness
     * @return mixed
     */
    public function ellipse(PointInterface $center, BoxInterface  $size, $color, $fill = false, $thickness = 1);

    /**
     * 画直线
     * @param PointInterface $start
     * @param PointInterface $end
     * @param $outline
     * @param int $thickness
     * @return mixed
     */
    public function line(PointInterface $start, PointInterface $end, $outline, $thickness = 1);

    /**
     * 画扇形
     * @param PointInterface $center
     * @param BoxInterface $size
     * @param $start
     * @param $end
     * @param $color
     * @param false $fill
     * @param int $thickness
     * @return mixed
     */
    public function pieSlice(PointInterface $center, BoxInterface  $size, $start, $end, $color, $fill = false, $thickness = 1);

    /**
     * 画点
     * @param PointInterface $position
     * @param $color
     * @return mixed
     */
    public function dot(PointInterface $position, $color);

    /**
     * 画长方体
     * @param PointInterface $leftTop
     * @param PointInterface $rightBottom
     * @param $color
     * @param false $fill
     * @param int $thickness
     * @return mixed
     */
    public function rectangle(PointInterface $leftTop, PointInterface $rightBottom, $color, $fill = false, $thickness = 1);

    /**
     * 画多边形
     * @param array $coordinates
     * @param $color
     * @param false $fill
     * @param int $thickness
     * @return static
     */
    public function polygon(array $coordinates, $color, $fill = false, $thickness = 1);

    /**
     * @param $string
     * @param FontInterface $font
     * @param PointInterface $position
     * @param int $angle
     * @param null $width
     * @return static
     */
    public function text($string, FontInterface $font, PointInterface $position, $angle = 0, $width = null);

    /**
     * @param $string
     * @param FontInterface $font
     * @param int $angle
     * @return BoxInterface
     */
    public function fontSize($string, FontInterface $font, $angle = 0);

    public function transparent($color);

    public function gamma($correction);

    public function negative();

    public function grayscale();

    public function colorize($color);

    public function sharpen();

    public function blur($sigma);

    public function brightness($brightness);

    public function convolve(Matrix $matrix);

    /**
     * 转化成可接受的颜色
     * @param $color
     * @return mixed
     */
    public function converterToColor($color);

    /**
     * 把颜色转成RGBA格式
     * @param $color
     * @return mixed
     */
    public function converterFromColor($color);
}