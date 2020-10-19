<?php
namespace Zodream\Image;

use Zodream\Image\Base\Box;
use Zodream\Image\Base\BoxInterface;
use Zodream\Image\Base\Point;

/**
 * Class Ico
 * @package Zodream\Image
 * @see https://github.com/chrisbliss18/php-ico
 */
class Ico extends Image {


    /**
     * 获取默认尺寸
     * @param bool $isApplication
     * @return int[]
     */
    public function getSizes($isApplication = true) {
        return $isApplication ? [256, 128, 64, 48, 32, 24, 16] :
            [256, 128, 96, 64, 48, 40, 32, 24, 22, 20, 16, 14, 10, 8];
    }

    /**
     * @param string $output
     * @param array $sizes
     * @return bool
     */
    public function saveAsSize($output, array $sizes = []) {
        if (empty($sizes)) {
            $sizes = [$this->instance()->getSize()];
        }
        $images = [];
        foreach ($sizes as $size) {
            $image = $this->createLayer($size);
            if (!empty($image)) {
                $images[] = $image;
            }
        }
        $data = pack( 'vvv', 0, 1, count( $images ) );
        $pixel_data = '';
        $icon_dir_entry_size = 16;
        $offset = 6 + ( $icon_dir_entry_size * count( $images ) );
        foreach ( $images as $image ) {
            $data .= pack( 'CCCCvvVV', $image['width'], $image['height'], $image['color_palette_colors'], 0, 1, $image['bits_per_pixel'], $image['size'], $offset );
            $pixel_data .= $image['data'];
            $offset += $image['size'];
        }
        $data .= $pixel_data;
        unset( $pixel_data );
        if ( false === ( $fh = fopen((string)$output, 'w' ) ) )
            return false;

        if ( false === (fwrite($fh, $data ) ) ) {
            fclose($fh);
            return false;
        }
        fclose($fh);
        return true;
    }

    private function createLayer($size) {
        if (is_array($size)) {
            list($width, $height) = $size;
        } elseif (is_string($size) && strpos($size, 'x') > 0) {
            list($width, $height) = explode('x', $size, 2);
        } elseif ($size instanceof BoxInterface) {
            $width = $size->getWidth();
            $height = $size->getHeight();
        } else {
            $height = $width = intval($size);
        }
        $new_im = clone $this->instance();
        $new_im->scale(new Box($width, $height));

        $pixel_data = array();
        $opacity_data = array();
        $current_opacity_val = 0;

        for ( $y = $height - 1; $y >= 0; $y-- ) {
            for ( $x = 0; $x < $width; $x++ ) {
                $color = $new_im->getColorAt(new Point($x, $y));

                $alpha = ( $color & 0x7F000000 ) >> 24;
                $alpha = ( 1 - ( $alpha / 127 ) ) * 255;

                $color &= 0xFFFFFF;
                $color |= 0xFF000000 & ( $alpha << 24 );

                $pixel_data[] = $color;


                $opacity = ( $alpha <= 127 ) ? 1 : 0;

                $current_opacity_val = ( $current_opacity_val << 1 ) | $opacity;

                if ( ( ( $x + 1 ) % 32 ) == 0 ) {
                    $opacity_data[] = $current_opacity_val;
                    $current_opacity_val = 0;
                }
            }

            if ( ( $x % 32 ) > 0 ) {
                while ( ( $x++ % 32 ) > 0 )
                    $current_opacity_val = $current_opacity_val << 1;

                $opacity_data[] = $current_opacity_val;
                $current_opacity_val = 0;
            }
        }

        $image_header_size = 40;
        $color_mask_size = $width * $height * 4;
        $opacity_mask_size = ( ceil( $width / 32 ) * 4 ) * $height;


        $data = pack( 'VVVvvVVVVVV', 40, $width, ( $height * 2 ), 1, 32, 0, 0, 0, 0, 0, 0 );

        foreach ( $pixel_data as $color )
            $data .= pack( 'V', $color );

        foreach ( $opacity_data as $opacity )
            $data .= pack( 'N', $opacity );


        return [
            'width'                => $width,
            'height'               => $height,
            'color_palette_colors' => 0,
            'bits_per_pixel'       => 32,
            'size'                 => $image_header_size + $color_mask_size + $opacity_mask_size,
            'data'                 => $data,
        ];
    }
}