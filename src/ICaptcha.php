<?php
declare(strict_types=1);
namespace Zodream\Image;

interface ICaptcha {

    public function setConfigs(array $configs): void;

    /**
     * 是否就是当前图片，能直接输出
     * @return bool
     */
    public function isOnlyImage(): bool;

    /**
     * 生成并返回结果
     * @return mixed
     */
    public function generate(): mixed;

    /**
     * 使用生成的结果和用户提交的结果进行验证
     * @param mixed $value
     * @param mixed $source
     * @return bool
     */
    public function verify(mixed $value, mixed $source): bool;

    /**
     * 将显示的数据转数组传给前台
     * @return array
     */
    public function toArray(): array;
}