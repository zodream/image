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

[内容生成图片](#draw)


### ico 生成

```php
use Zodream\Image\Ico;

$image = new Ico('1.png');
$image->saveAsSize('1.ico', $image->getSizes());
```

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

<a name="water"></a>
### 水印

文字水印

```php
$image = new WaterMark();
$image->instance()->loadResource('1.jpg');
$image->addTextByDirection('water');
```

自定义水印
```php
$image = new WaterMark();
$image->instance()->loadResource($file);
$font = new Font((string)app_path(config('disk.font')), 12, '#fff');
$textBox = $image->instance()->fontSize($text, $font);
// 根据文字的尺寸获取水印的位置
list($x, $y) = $image->getPointByDirection(WaterMark::RightTop, $textBox->getWidth(), $textBox->getHeight(), 20);
// 给文字添加阴影
$image->addText($text, $x + 2, $y + 2, $font->getSize(), '#777', $font->getFile());
$image->addText($text, $x, $y, $font->getSize(), $font->getColor(), $font->getFile());
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

```php

$goods = new File('html\assets\images\zd.jpg');
$qr = new File('html\assets\images\wx.jpg');

$font = 'data\fonts\msyh.ttc';

$img = new Canvas();
$img->create(402, 712);
$img->setBackground('#fff')
    ->addImage(new Image($goods), new Box(0, 60, 402, 402))
    ->addImage(new Image($qr), new Box(18, 590, 106, 106))
    ->addText(new Text('请长按识别二维码', 18, 560, [155, 143, 128], $font, 12))
    ->addText(new Text('微信支付购买', 18, 576, [155, 143, 128], $font, 12))
    ->addText(new Text('kiwigo', 300, 560, '#000', $font, 16))
    ->addText(new Text('￥', 278, 640, '#f00', $font, 12))
    ->addText(new Text('123.00', 290, 640, '#f00', $font, 25))
    ->addText(new Text('测试商品', 300, 670, '#000', $font, 12))
```

<a name="draw"></a>
## 内容生成图片

```php
$str = <<<TEXT



[padding=10 background=#fff width=470]
[img width=450 height=450]aaa
[size=20 padding=10,0 bold]sbfajahaa
[color=#ccc size=12]sdfsssdfdafsdaasfs
[img width=100 height=100 center]iadfasdsad
[size=10 color=#ccc center]123131231
TEXT;
$box = BoxNode::parse($str);
dd($box);


$img = __DIR__.'/assets/images/banner.jpg';
$font = __DIR__.'/../data/fonts/msyh.ttc';
$box = BoxNode::create([
    'padding' => 10,
    'background' => 'white',
    'width' => 470
])->append(
    ImgNode::create($img, [
        'width' => '100%',
        'height' => '100%'
    ]),
    TextNode::create('sbfajahaa', [
        'size' => 20,
        'letterSpace' => 20,
        'padding' => [
            10,
            0,
        ],
        'bold' => true,
        'font' => $font
    ]),
    TextNode::create('1234avccg', [
        'size' => 12,
        'font' => $font,
        'letterSpace' => 4,
        'lineSpace' => 4,
        'color' => '#ccc'
    ]),
    ImgNode::create($img, [
        'width' => '100',
        'height' => '100',
        'center' => true
    ]),
    TextNode::create('sbfajahaa', [
        'size' => 12,
        'color' => '#ccc',
        'letterSpace' => 4,
        'lineSpace' => 4,
        'wrap' => false,
        'font' => $font,
        'center' => true
    ]),
    BorderNode::create([
        'size' => 1,
        'fixed' => true,
        'margin' => 10
    ]),
    LineNode::create(10, 10, 10, 100, [
        'size' => 1,
        'fixed' => true,
        'color' => 'black'
    ]),
    RectNode::create([
        'points' => [
            [0, 0],
            [200, 0],
            [0, 200],
        ],
        'color' => 'black'
    ])
);
$box->draw()->show();

```