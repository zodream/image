<?php
declare(strict_types=1);
namespace Zodream\Image;
/**
 * 验证码
 *
 * @author Jason
 */

use Zodream\Helpers\Security\Hash;
use Zodream\Image\Base\Box;
use Zodream\Image\Base\Font;
use Zodream\Image\Base\Point;
use Zodream\Infrastructure\Concerns\ConfigTrait;

class Captcha extends Image {

    use ConfigTrait;

    const SESSION_KEY = 'captcha';

    protected $configKey = 'captcha';
    protected int $width;
    protected int $height;

    /**
     * @var string 验证码结果
     */
    protected string $code;

    /**
     * @var array 验证码图片内容字符串
     */
    protected array $chars = [];

    protected array $configs = [
        'characters' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', //随机因子
        'length' => 4,    //验证码长度
        'fontSize' => 0,   //指定字体大小
        'fontColor' => '',   //指定字体颜色, 可以是数组
        'fontFamily' => 3,  //指定的字体
        'width' => 100,
        'height' => 30,
        'angle' => 0,         //角度
        'sensitive' => true,   // 大小写敏感
        'mode' => 0           // 验证码模式： 0 文字 1 公式
    ];

    public function getRealType() {
        return 'png';
    }

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
    public function generate(int $level = 0) {
        $this->getCode();
        $this->loadConfigs();
        $this->width = $this->configs['width'];
        $this->height = $this->configs['height'];
        $this->createBg();
        $this->createText();
        $this->createLine($level);
        $this->instance()->setRealType($this->getRealType());
        return $this;
    }


    /**
     * 生成随机码
     * @param bool $setSession 保存到session里
     * @return $this
     * @throws \Exception
     */
    public function createCode(bool $setSession = true) {
        list($this->code, $this->chars) = $this->configs['mode'] == 1
            ? $this->generateFormula() : $this->generateRandomChar();
        if ($setSession) {
            session()->set(self::SESSION_KEY, [
                'sensitive' => $this->configs['sensitive'],
                'key'       => Hash::make($this->configs['sensitive'] || is_numeric($this->code) ? $this->code : strtolower($this->code))
            ]);
        }
        return $this;
    }

    protected function generateRandomChar(): array {
        $charset = $this->configs['characters'];
        $_len   = strlen($charset) - 1;
        $count = intval($this->configs['length']);
        $chars = [];
        for ($i = 0; $i < $count; $i ++) {
            $chars[] = $charset[mt_rand(0, $_len)];
        }
        return [implode('', $chars), $chars];
    }

    protected function generateFormula(): array {
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
    protected function createBg(): void {
        $this->instance()->create(new Box($this->width, $this->height),
            [mt_rand(157, 255), mt_rand(157, 255), mt_rand(157, 255)]);
    }

    /**
     * 生成文字
     */
    protected function createText(): void {
        $length = count($this->chars);
        $width = $this->width / ($length + 1);
        $left = $width * .5;
        $maxHeight = (int)($this->height - $left);
        for ($i = 0; $i < $length; $i ++) {
            $size = $this->fontSize();
            $angle = $size > $this->height  ? 0 : $this->angle();
            $height = intval((abs(cos($angle)) + abs(sin($angle))) * $size);
            $this->instance()->text(
                $this->chars[$i],
                 new Font($this->configs['fontFamily'], $size, $this->fontColor($i)),
                 new Point((int)($left + $width * $i),
                     $height > $maxHeight
                         ? $height : mt_rand($height, $maxHeight)),
                $angle
            );
        }
    }

    /**
     *
     * @return int
     */
    protected function angle(): int {
        if (empty($this->configs['angle'])) {
            return mt_rand(-30, 30);
        }
        return mt_rand(-1 * $this->configs['angle'], $this->configs['angle']);
    }

    protected function fontSize(): int {
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
    protected function fontColor(int $i): mixed {
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
    protected function createLine(int $level = 1) {
        //线条
        for ($i = 0; $i < 6; $i ++) {
            $this->instance()->line(
                new Point(mt_rand(0, $this->width),
                    mt_rand(0, $this->height)),
                new Point(mt_rand(0, $this->width),
                    mt_rand(0, $this->height)),
                [mt_rand(0, 156), mt_rand(0, 156), mt_rand(0, 156)]);
        }
        //雪花
        for ($i = 0, $length = $level * 20; $i < $length; $i ++) {
            $this->instance()->text(
                '*',
                new Font(mt_rand(1, 5), 16, [mt_rand(200, 255), mt_rand(200, 255), mt_rand(200, 255)]),
                new Point(mt_rand(0, $this->width),
                    mt_rand(0, $this->height))
            );
        }
    }

    /**
     * 验证
     * @param string $value
     * @return bool
     * @throws \Exception
     */
    public function verify(string $value): bool {
        if (!session()->has(self::SESSION_KEY)) {
            return false;
        }
        $data = session()->get(self::SESSION_KEY);
        if (!$data['sensitive'] && !is_numeric($value)) {
            $value = strtolower($value);
        }
        session()->delete(self::SESSION_KEY);
        return Hash::verify($value, $data['key']);
    }
}