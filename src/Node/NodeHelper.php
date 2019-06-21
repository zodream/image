<?php
namespace Zodream\Image\Node;

class NodeHelper {

    public static function padding(array $properties, $key = 'padding') {
        if (!isset($properties[$key])) {
            return [0, 0, 0, 0];
        }
        $property = $properties[$key];
        if (!is_array($property)) {
            $property = explode(',', $property);
        }
        $count = count($property);
        if ($count === 1) {
            return [$property[0], $property[0], $property[0], $property[0]];
        }
        if ($count === 2) {
            return [$property[0], $property[1], $property[0], $property[1]];
        }
        if ($count === 3) {
            return [$property[0], $property[1], $property[2], $property[1]];
        }
        return $property;
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
            return $arg * $parentProperties['outerWidth'] / 100;
        }
        return $arg * $parentProperties['outerHeight'] / 100;
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