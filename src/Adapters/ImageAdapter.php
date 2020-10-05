<?php
namespace Zodream\Image\Adapters;

interface ImageAdapter {

    public function create($size, $color = null);

    public function open($path);

    public function load($string);

    public function read($resource);

    public function getHeight();

    public function getWidth();

    public function scale($ratio);

    public function getColorAt($point);

    public function copy();

    public function crop($start, $size);

    public function resize($size, $filter);

    public function rotate($angle, $background = null);

    public function paste(ImageAdapter $image, $start, $alpha = 100);

    public function save($path = null, array $options = array());

    public function show($format, array $options = array());

    public function thumbnail($size, $settings, $filter);

    public function fill($fill);


    public function arc($center, $size, $start, $end, $color, $thickness = 1);

    public function chord($center, $size, $start, $end, $color, $fill = false, $thickness = 1);

    public function circle($center, $radius, $color, $fill = false, $thickness = 1);

    public function ellipse($center, $size, $color, $fill = false, $thickness = 1);

    public function line($start, $end, $outline, $thickness = 1);

    public function pieSlice($center, $size, $start, $end, $color, $fill = false, $thickness = 1);

    public function dot($position, $color);

    public function rectangle($leftTop, $rightBottom, $color, $fill = false, $thickness = 1);

    public function polygon(array $coordinates, $color, $fill = false, $thickness = 1);

    public function text($string, $font, $position, $angle = 0, $width = null);



    public function gamma($correction);

    public function negative();

    public function grayscale();

    public function colorize($color);

    public function sharpen();

    public function blur($sigma);

    public function brightness($brightness);

    public function convolve($matrix);
}