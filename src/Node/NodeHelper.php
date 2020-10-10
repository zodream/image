<?php
namespace Zodream\Image\Node;

use phpDocumentor\Reflection\Types\This;

class NodeHelper {

    public static function padding(array $properties, $key = 'padding') {
        $formatVal = function (array $data) use ($key, $properties) {
            $map = $key === 'radius' ?
                ['top-left', 'top-right', 'bottom-right', 'bottom-left']
                : ['top', 'right', 'bottom', 'left'];
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

    public static function step($cb, $start, $end, $step = 1) {
        $diff = ($start > $end ? -1 : 1) * abs($step === 0 ? 1 : $step);
        while (true) {
            $cb($start);
            if (($diff > 0 && $start >= $end) || ($diff < 0 && $start <= $end)) {
                return;
            }
            $start += $step;
            if (($diff > 0 && $start > $end) || ($diff < 0 && $start < $end)) {
                $start = $end;
            }
        }
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

}