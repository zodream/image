<?php
declare(strict_types=1);
namespace Zodream\Image;


use Zodream\Helpers\Str;
use Exception;

class Colors {

    private static array $maps = [
        'AliceBlue' => '240,248,255',
        'LightSalmon' => '255,160,122',
        'AntiqueWhite' => '250,235,215',
        'LightSeaGreen' => '32,178,170',
        'Aqua' => '0,255,255',
        'LightSkyBlue' => '135,206,250',
        'Aquamarine' => '127,255,212',
        'LightSlateGray' => '119,136,153',
        'Azure' => '240,255,255',
        'LightSteelBlue' => '176,196,222',
        'Beige' => '245,245,220',
        'LightYellow' => '255,255,224',
        'Bisque' => '255,228,196',
        'Lime' => '0,255,0',
        'Black' => '0,0,0',
        'LimeGreen' => '50,205,50',
        'BlanchedAlmond' => '255,255,205',
        'Linen' => '250,240,230',
        'Blue' => '0,0,255',
        'Magenta' => '255,0,255',
        'BlueViolet' => '138,43,226',
        'Maroon' => '128,0,0',
        'Brown' => '165,42,42',
        'MediumAquamarine' => '102,205,170',
        'BurlyWood' => '222,184,135',
        'MediumBlue' => '0,0,205',
        'CadetBlue' => '95,158,160',
        'MediumOrchid' => '186,85,211',
        'Chartreuse' => '127,255,0',
        'MediumPurple' => '147,112,219',
        'Chocolate' => '210,105,30',
        'MediumSeaGreen' => '60,179,113',
        'Coral' => '255,127,80',
        'MediumSlateBlue' => '123,104,238',
        'CornflowerBlue' => '100,149,237',
        'MediumSpringGreen' => '0,250,154',
        'Cornsilk' => '255,248,220',
        'MediumTurquoise' => '72,209,204',
        'Crimson' => '220,20,60',
        'MediumVioletRed' => '199,21,112',
        'Cyan' => '0,255,255',
        'MidnightBlue' => '25,25,112',
        'DarkBlue' => '0,0,139',
        'MintCream' => '245,255,250',
        'DarkCyan' => '0,139,139',
        'MistyRose' => '255,228,225',
        'DarkGoldenrod' => '184,134,11',
        'Moccasin' => '255,228,181',
        'DarkGray' => '169,169,169',
        'NavajoWhite' => '255,222,173',
        'DarkGreen' => '0,100,0',
        'Navy' => '0,0,128',
        'DarkKhaki' => '189,183,107',
        'OldLace' => '253,245,230',
        'DarkMagena' => '139,0,139',
        'Olive' => '128,128,0',
        'DarkOliveGreen' => '85,107,47',
        'OliveDrab' => '107,142,45',
        'DarkOrange' => '255,140,0',
        'Orange' => '255,165,0',
        'DarkOrchid' => '153,50,204',
        'OrangeRed' => '255,69,0',
        'DarkRed' => '139,0,0',
        'Orchid' => '218,112,214',
        'DarkSalmon' => '233,150,122',
        'PaleGoldenrod' => '238,232,170',
        'DarkSeaGreen' => '143,188,143',
        'PaleGreen' => '152,251,152',
        'DarkSlateBlue' => '72,61,139',
        'PaleTurquoise' => '175,238,238',
        'DarkSlateGray' => '40,79,79',
        'PaleVioletRed' => '219,112,147',
        'DarkTurquoise' => '0,206,209',
        'PapayaWhip' => '255,239,213',
        'DarkViolet' => '148,0,211',
        'PeachPuff' => '255,218,155',
        'DeepPink' => '255,20,147',
        'Peru' => '205,133,63',
        'DeepSkyBlue' => '0,191,255',
        'Pink' => '255,192,203',
        'DimGray' => '105,105,105',
        'Plum' => '221,160,221',
        'DodgerBlue' => '30,144,255',
        'PowderBlue' => '176,224,230',
        'Firebrick' => '178,34,34',
        'Purple' => '128,0,128',
        'FloralWhite' => '255,250,240',
        'Red' => '255,0,0',
        'ForestGreen' => '34,139,34',
        'RosyBrown' => '188,143,143',
        'Fuschia' => '255,0,255',
        'RoyalBlue' => '65,105,225',
        'Gainsboro' => '220,220,220',
        'SaddleBrown' => '139,69,19',
        'GhostWhite' => '248,248,255',
        'Salmon' => '250,128,114',
        'Gold' => '255,215,0',
        'SandyBrown' => '244,164,96',
        'Goldenrod' => '218,165,32',
        'SeaGreen' => '46,139,87',
        'Gray' => '128,128,128',
        'Seashell' => '255,245,238',
        'Green' => '0,128,0',
        'Sienna' => '160,82,45',
        'GreenYellow' => '173,255,47',
        'Silver' => '192,192,192',
        'Honeydew' => '240,255,240',
        'SkyBlue' => '135,206,235',
        'HotPink' => '255,105,180',
        'SlateBlue' => '106,90,205',
        'IndianRed' => '205,92,92',
        'SlateGray' => '112,128,144',
        'Indigo' => '75,0,130',
        'Snow' => '255,250,250',
        'Ivory' => '255,240,240',
        'SpringGreen' => '0,255,127',
        'Khaki' => '240,230,140',
        'SteelBlue' => '70,130,180',
        'Lavender' => '230,230,250',
        'Tan' => '210,180,140',
        'LavenderBlush' => '255,240,245',
        'Teal' => '0,128,128',
        'LawnGreen' => '124,252,0',
        'Thistle' => '216,191,216',
        'LemonChiffon' => '255,250,205',
        'Tomato' => '253,99,71',
        'LightBlue' => '173,216,230',
        'Turquoise' => '64,224,208',
        'LightCoral' => '240,128,128',
        'Violet' => '238,130,238',
        'LightCyan' => '224,255,255',
        'Wheat' => '245,222,179',
        'LightGoldenrodYellow' => '250,250,210',
        'White' => '255,255,255',
        'LightGreen' => '144,238,144',
        'WhiteSmoke' => '245,245,245',
        'LightGray' => '211,211,211',
        'Yellow' => '255,255,0',
        'LightPink' => '255,182,193',
        'YellowGreen' => '154,205,50',
    ];

    public static function converter(mixed $color): int|string|array {
        if (func_num_args() == 1 && is_int($color)) {
            return $color;
        }
        if (func_num_args() >= 3) {
            return func_num_args();
        }
        if (is_array($color) && count($color) >= 3) {
            return $color;
        }
        if (is_string($color) && str_starts_with($color, '#')) {
            return static::transformRGB($color);
        }
        $name = Str::studly((string)$color);
        if (isset(self::$maps[$name])) {
            $args = explode(',', self::$maps[$name], 3);
            $args[] = 1;
            return $args;
        }
        throw new Exception(
            __('{color} IS ERROR!', [
                'color' => $color
            ])
        );
    }

    public static function transformRGB(string $color = '#000000'): array {
        if (strlen($color) == 4) {
            $red = substr($color, 1, 1);
            $green = substr($color, 2, 1);
            $blue = substr($color, 3, 1);
            $red .= $red;
            $green .= $green;
            $blue .= $blue;
        } else {
            $red = substr($color, 1, 2);
            $green = substr($color, 3, 2);
            $blue = substr($color, 5, 2);
        }
        return array(
            hexdec($red),
            hexdec($green),
            hexdec($blue),
            1,
        );
    }
}