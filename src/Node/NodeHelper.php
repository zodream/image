<?php
namespace Zodream\Image\Node;

use phpDocumentor\Reflection\Types\This;

class NodeHelper {

    public static function padding(array $properties, $key = 'padding') {
        $formatVal = function (array $data) use ($key, $properties) {
            $map = ['top', 'right', 'bottom', 'left'];
            foreach ($data as $i => $val) {
                if (count($map) <= $i) {
                    break;
                }
                $name = sprintf('%s-%s', $key, $map[$i]);
                if (isset($properties[$name])) {
                    $data[$i] = $properties[$name];
                }
            }
            return $data;
        };
        if (!isset($properties[$key])) {
            return $formatVal([0, 0, 0, 0]);
        }
        $property = $properties[$key];
        if (!is_array($property)) {
            $property = explode(',', $property);
        }
        $count = count($property);
        if ($count === 1) {
            return $formatVal([$property[0], $property[0], $property[0], $property[0]]);
        }
        if ($count === 2) {
            return $formatVal([$property[0], $property[1], $property[0], $property[1]]);
        }
        if ($count === 3) {
            return $formatVal([$property[0], $property[1], $property[2], $property[1]]);
        }
        return $formatVal($property);
    }

    public static function width($value, array $parentProperties) {
        if (empty($value)) {
            return null;
        }
        if (is_numeric($value)) {
            return $value;
        }
        if (!preg_match('/([\d\.]+)\s*(%|vw|vh)/', $value, $match)) {
            return null;
        }

        $arg = floatval($match[1]);
        if ($match[2] === '%') {
            return $arg * $parentProperties['innerWidth'] / 100;
        }
        if ($match[2] === 'vw') {
            return $arg * $parentProperties['viewWidth'] / 100;
        }
        return $arg * $parentProperties['viewHeight'] / 100;
    }

    public static function orDefault($key, array $args, array $args1, $default = 0) {
        if (isset($args[$key])) {
            return $args[$key];
        }
        if (isset($args1[$key])) {
            return $args1[$key];
        }
        return $default;
    }

    public static function mergeStyle(array $styles, array $parentStyles) {
        if (!isset($parentStyles['viewWidth'])) {
            $parentStyles['viewWidth'] = $styles['width'];
            $parentStyles['viewHeight'] = isset($styles['height']) ? $styles['height'] : 0;
        }
        $styles['padding'] = NodeHelper::padding($styles);
        $styles['margin'] = NodeHelper::padding($styles, 'margin');
        $parentInnerWidth = isset($parentStyles['innerWidth']) ? $parentStyles['innerWidth'] : $styles['width'];
        if (isset($styles['width'])) {
            $styles['outerWidth'] = $styles['width'] + $styles['margin'][1] + $styles['margin'][3];
        } else {
            $styles['outerWidth'] = $parentInnerWidth;
            $styles['width'] = $parentInnerWidth - $styles['margin'][1] - $styles['margin'][3];
        }
        $styles['innerWidth'] = $styles['width'] - $styles['padding'][1] - $styles['padding'][3];
        if (isset($styles['x'])) {
            $styles['x'] += $parentStyles['x'] + $styles['margin'][1] + $styles['padding'][1];
        } elseif (isset($styles['margin-left'])) {
            $styles['x'] = $parentStyles['brotherRight'] + $styles['margin'][1] + $styles['padding'][1];
            $styles['y'] = $parentStyles['brotherTop'] + $styles['margin'][0] + $styles['padding'][0];
            $styles['position'] = 'absolute';
        } else {
            $styles['x'] = (isset($parentStyles['x']) ? $parentStyles['x'] : 0)
                + $styles['margin'][1] + $styles['padding'][1];
        }
        if (!isset($styles['y'])) {
            $styles['y'] = (isset($parentStyles['y']) ? $parentStyles['y'] : 0)
                + $styles['margin'][0] + $styles['padding'][0];
        }
        $copyKeys = ['color', 'font-size', 'viewWidth', 'viewHeight'];
        foreach ($copyKeys as $key) {
            if (isset($styles[$key])) {
                continue;
            }
            $styles[$key] = $parentStyles[$key];
        }
        return $styles;
    }
}