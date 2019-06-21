<?php
namespace Zodream\Image;
/**
 * 验证码
 *
 * @author Jason
 */
use Zodream\Infrastructure\Security\Hash;
use Zodream\Infrastructure\Traits\ConfigTrait;
use Zodream\Service\Factory;

class Captcha extends WaterMark {

    use ConfigTrait;

    const SESSION_KEY = 'captcha';

    protected $configKey = 'captcha';

    protected $realType = 'png';

    /**
     * @var string 验证码结果
     */
    protected $code;

    /**
     * @var array 验证码图片内容字符串
     */
    protected $chars = [];

    protected $configs = [
        'characters' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', //随机因子
        'length' => 4,    //验证码长度
        'fontSize' => 0,   //指定字体大小
        'fontColor' => '',   //指定字体颜色, 可以是数组
        'fontFamily' => null,  //指定的字体
        'width' => 100,
        'height' => 30,
        'angle' => 0,         //角度
        'sensitive' => true,   // 大小写敏感
        'mode' => 0           // 验证码模式： 0 文字 1 公式
    ];

    /**
     * 获取验证码
     * @return string
     * @throws \Exception
     */
    public function getCode() {
        if (empty($this->code)) {
            $this->createCode();
        }
        return $this->code;
    }

    /**
     * 生成
     * @param int $level 干扰等级
     * @return $this
     * @throws \Exception
     */
    public function generate($level = 0) {
        $this->getCode();
        $this->loadConfigs();
        $this->width = $this->configs['width'];
        $this->height = $this->configs['height'];
        $this->createBg();
        $this->createText();
        $this->createLine($level);
        return $this;
    }


    /**
     * 生成随机码
     * @param bool $setSession 保存到session里
     * @return $this
     * @throws \Exception
     */
    public function createCode($setSession = true) {
        list($this->code, $this->chars) = $this->configs['mode'] == 1
            ? $this->generateFormula() : $this->generateRandomChar();
        if ($setSession) {
            Factory::session()->set(self::SESSION_KEY, [
                'sensitive' => $this->configs['sensitive'],
                'key'       => Hash::make($this->configs['sensitive'] || is_numeric($this->code) ? $this->code : strtolower($this->code))
            ]);
        }
        return $this;
    }

    protected function generateRandomChar() {
        $charset = $this->configs['characters'];
        $_len   = strlen($charset) - 1;
        $count = intval($this->configs['length']);
        $chars = [];
        for ($i = 0; $i < $count; $i ++) {
            $chars[] = $charset[mt_rand(0, $_len)];
        }
        return [implode('', $chars), $chars];
    }

    protected function generateFormula() {
        $tags = is_numeric($this->configs['fontFamily']) ?
            ['+', '-', '*', '/'] : ['加', '减', '乘', '除'];
        $tag = mt_rand(0, 3);
        $first = mt_rand(1, 99);
        $second = mt_rand(1, 99);
        $result = 0;
        if ($tag == 0) {
            $result = $first + $second;
        } elseif ($tag == 1) {
            if ($first < $second) {
                list($first, $second) = [$second, $first];
            }
            $result = $first - $second;
        } elseif ($tag == 2) {
            $result = $first * $second;
        } elseif ($tag == 3) {
            if ($first < $second) {
                list($first, $second) = [$second, $first];
            }
            $result = floor($first / $second);
            $first = $result * $second;
        }
        return [$result, [$first, $tags[$tag], $second, '=?']];
    }

    /**
     * 生成背景
     */
    protected function createBg() {
        $this->create($this->width, $this->height);
        $this->fill(
            [mt_rand(157, 255), mt_rand(157, 255), mt_rand(157, 255)]
        );
    }

    /**
     * 生成文字
     */
    protected function createText() {
        $length = count($this->chars);
        $width = $this->width / ($length + 1);
        $left = $width * .5;
        $maxHeight = $this->height - $left;
        for ($i = 0; $i < $length; $i ++) {
            $size = $this->fontSize();
            $angle = $size > $this->height  ? 0 : $this->angle();
            $height = (abs(cos($angle)) + abs(sin($angle))) * $size;
            $this->addText(
                $this->chars[$i],
                 $left + $width * $i,
                $height > $maxHeight
                    ? $height : mt_rand($height, $maxHeight),
                $size,
                $this->fontColor($i),
                $this->configs['fontFamily'],
                $angle
            );
        }
    }

    /**
     *
     * @return int
     */
    protected function angle() {
        if (empty($this->configs['angle'])) {
            return mt_rand(-30, 30);
        }
        return mt_rand(-1 * $this->configs['angle'], $this->configs['angle']);
    }

    protected function fontSize() {
        if (!empty($this->configs['fontSize'])) {
            return $this->configs['fontSize'];
        }
        return rand($this->height - 10, $this->height);
    }

    /**
     * 获取字体颜色
     * @param integer $i
     * @return array|mixed
     */
    protected function fontColor($i) {
        if (empty($this->configs['fontColor'])) {
            return [mt_rand(0, 156), mt_rand(0, 156), mt_rand(0, 156)];
        }
        if (!is_array($this->configs['fontColor'])) {
            return $this->configs['fontColor'];
        }
        if (count($this->configs['fontColor']) <= $i) {
            return [mt_rand(0, 156), mt_rand(0, 156), mt_rand(0, 156)];
        }
        return $this->configs['fontColor'][$i];
    }

    /**
     * 生成线条、雪花
     * @param int $level
     */
    protected function createLine($level = 1) {
        //线条
        for ($i = 0; $i < 6; $i ++) {
            $this->line(
                mt_rand(0, $this->width),
                mt_rand(0, $this->height),
                mt_rand(0, $this->width),
                mt_rand(0, $this->height),
                [mt_rand(0, 156), mt_rand(0, 156), mt_rand(0, 156)]);
        }
        //雪花
        for ($i = 0, $length = $level * 20; $i < $length; $i ++) {
            $this->addText(
                '*',
                mt_rand(0, $this->width),
                mt_rand(0, $this->height),
                16,
                [mt_rand(200, 255), mt_rand(200, 255), mt_rand(200, 255)],
                mt_rand(1, 5)
            );
        }
    }

    /**
     * 验证
     * @param string $value
     * @return bool
     * @throws \Exception
     */
    public function verify($value) {
        if (!Factory::session()->has(self::SESSION_KEY)) {
            return false;
        }
        $data = Factory::session()->get(self::SESSION_KEY);
        if (!$data['sensitive'] && !is_numeric($value)) {
            $value = strtolower($value);
        }
        Factory::session()->delete(self::SESSION_KEY);
        return Hash::verify($value, $data['key']);
    }
}