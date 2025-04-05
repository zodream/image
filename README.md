# image
使用 gd 对图片处理的封装，包含 验证码、水印、缩略图、二维码、图片拖拽验证

## 当前状态：一直开发中

## 简单使用教程

[验证码](#captcha)

[滑动验证码](#slider)

[文字点击验证](#hint)

[二维码](#qr)

[水印](#water)

[缩略图](#thumb)

[图片比较（简单版）](#compare)

[内容生成图片](#draw)


### 使用 iconfont 等类似字体图标

```php
$image = new Image();
$res = $image->instance();
$res->create(ImageManager::createSize(200, 200));
$res->fill('#fff');
$res->text("\u{e709}", ImageManager::createFont(app_path('data/fonts/iconfont.ttf')), ImageManager::createPoint(100, 100));
$image->show();
```

** 请注意：`\u{e709}` 表示一个字符，且字符串必须用双引号 **

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
$source = $captcha->generate();

$captcha->verify($_POST['captcha'], $source);
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

$captcha = new SlideCaptcha();
$captcha->setConfigs([
    'width' => 300,
    'height' => 130,
]);
$captcha->instance()->open('bg.jpg');
$captcha->setShape('shape.jpg'); // 根据图片抠图
$source = $captcha->generate();

$captcha->verify($_POST['captcha'], $source);

$imgData = $captcha->toArray();
$html = '';
foreach ($imgData['imageItems'] as $point) {
    $html .= sprintf('<div class="slide-img" style="background-position: %spx %spx"></div>', $point['x'], $point['y']);
}

$html = <<<HTML
<style>
.slide-box {
    width: {$imgData['width']}px;
    height: {$imgData['height']}px;
    position: relative;
}
.slide-box .slide-img {
    float: left;
    margin: 0;
    padding: 0;
    background-image: url({$imgData['image']});
    background-repeat: no-repeat;
    width: {$imgData['imageItems'][0]['width']}px;
    height: {$imgData['imageItems'][0]['height']}px;
}
.slide-box .slide-cut {
    position: absolute;
    top: {$imgData['controlY']}px;
    background-image: url({$imgData['control']});
    background-repeat: no-repeat;
    width: {$imgData['controlWidth']}px;
    height: {$imgData['controlHeight']}px;
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

<a name="hint"></a>
### 点击验证码
依次点击图片上的文字

```php
$captcha = new HintCaptcha();
$items = ['我', '就', '你', '哈'];
$captcha->setConfigs([
    'width' => 300,
    'height' => 130,
    'fontSize' => 20,
    'fontFamily' => 'Yahei.ttf',
    'words' => $items,
    'count' => 3,
]);
$captcha->instance()->open('images/banner.jpg');
$source = $captcha->generate();

$captcha->verify($_POST['captcha'], $source);

$imgData = $captcha->toArray();
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
$box->beginDraw()->show();
```