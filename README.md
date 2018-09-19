# image
使用 gd 对图片处理的封装，包含 验证码、水印、缩略图、二维码、图片拖拽验证

## 当前状态：一直开发中

## 注意

验证码 需要配置 所以依赖 [zodream](https://github.com/zodream/zodream)


## 简单使用教程

[验证码](#captcha)

[二维码](#qr)

[水印](#water)

[缩略图](#thumb)

[滑动验证码](#slider)

[图片比较（简单版）](#compare)

[内容生成图片（未完成）](#draw)




<a name="captcha"></a>
### 验证码

```PHP
use Zodream\Image\Captcha;

$captcha = new Captcha();
$captcha->setConfigs([
    'width' => 200,
    'fontSize' => 20,
    'fontFamily' => 'Ubuntu_regular.ttf'
]);

```

默认配置

```PHP

[
    'characters' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', //随机因子
    'length' => 4,    //验证码长度
    'fontSize' => 0,   //指定字体大小
    'fontColor' => '',   //指定字体颜色, 可以是数组
    'fontFamily' => null,  //指定的字体
    'width' => 100,        // 图片宽
    'height' => 30,       // 图片高
    'angle' => 0,         //角度
    'sensitive' => true,   // 大小写敏感
    'mode' => 0           // 验证码模式： 0 文字 1 公式
]

```

<a name="qr"></a>
### 二维码

```PHP
use Zodream\Image\QrCode;

$qr = new QrCode();
$image = $qr->encode('123123');
$image->save();

```

<a name="slider"></a>
### 滑动验证码

```PHP
use Zodream\Image\SlideCaptcha;

$img = new SlideCaptcha('bg.jpg');
$img->scale(300, 130);
$img->setShape('shape.jpg'); // 根据图片抠图
$img->generate();

$args = range(0, 7);
shuffle($args);
list($bg, $points, $size) = $img->sortBy($args); // 根据生成的乱序数组打乱图片
$html = '';
foreach ($points as $point) {
    $html .= sprintf('<div class="slide-img" style="background-position: %spx %spx"></div>', $point[0], $point[1]);
}
$width = $img->getWidth();
$height = $img->getHeight();
$bg_data = $bg->toBase64();
$point = $img->getPoint(); // [x, y] 抠取的图片坐标
$html = <<<HTML
<style>
.slide-box {
    width: {$width}px;
    height: {$height}px;
    position: relative;
}
.slide-box .slide-img {
    float: left;
    margin: 0;
    padding: 0;
    background-image: url({$bg_data});
    background-repeat: no-repeat;
    width: {$size[0]}px;
    height: {$size[1]}px;
}
.slide-box .slide-cut {
    position: absolute;
    top: {$point[1]}px;
    background-image: url({$img->getSlideImage()->toBase64()});
    background-repeat: no-repeat;
    width: {$img->getSlideImage()->getWidth()}px;
    height: {$img->getSlideImage()->getHeight()}px;
    z-index: 9;
}
</style>
<div class="slide-box">
    <div class="slide-cut"></div>
    <div class="slide-list">
        {$html}
    </div>
    
</div>
HTML;

```