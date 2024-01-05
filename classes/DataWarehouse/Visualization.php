<?php
namespace DataWarehouse;
/*
 * contains utility functions
 * @author: Amin Ghadersohi
 * @date: 1/25/2014
 */
abstract class Visualization
{
    public static $thumbnail_width = 650;
    public static $default_width = 1000;
    public static function alterBrightness($color, $steps)
    {
        $a = ($color & 0xff000000) >> 24;
        $r = ($color & 0x00ff0000) >> 16;
        $g = ($color & 0x0000ff00) >> 8;
        $b = $color & 0x000000ff;
        if ($color > 0xffffff)
            $a = max(0, min(255, $a + $steps));
        $r = max(0, min(255, $r + $steps));
        $g = max(0, min(255, $g + $steps));
        $b = max(0, min(255, $b + $steps));
        return ($a << 24) + ($r << 16) + ($g << 8) + $b;
    }
    //http://martin.ankerl.com/2009/12/09/how-to-create-random-colors-programmatically/
    public static function getColors($count = NULL, $palleteIndex = 0, $includeWhite = true)
    {
        $ret = [];
        $colors = json_decode(COLORS);
        $colors = $colors[$palleteIndex];
        if ($count == NULL || count($colors) >= $count)
        {
            foreach ($colors as $result)
            {
                if($result[0] == 'FFFFFF' && !$includeWhite) {
                    continue;
                }
                $ret[] = hexdec($result[0]);
            }
        }
        $ret_count = count($ret);
        mt_srand($count);
        if ($count != NULL && $ret_count < $count)
        {
            $value = 15;
            $increment = 310.0 / ($count - $ret_count);
            for ($i = $ret_count; $i < $count; $i++)
            {
                $value = $value + $increment;
                $rgb = self::HSVtoRGB([$value / 360.0, random_int(80, 90) / 100.0, random_int(75, 99) / 100.0]);
                $color_string = sprintf("%02x%02x%02x", $rgb[0] * 255, $rgb[1] * 255, $rgb[2] * 255);
                $next_color = hexdec($color_string);
                $ret[] = $next_color;
            }
        }
        mt_srand();
        return $ret;
    }

    public static function HSVtoRGB(array $hsv)
    {
        [$H, $S, $V] = $hsv;
        //1
        $H *= 6;
        //2
        $I = (int)floor($H);
        $F = $H - $I;
        //3
        $M = $V * (1 - $S);
        $N = $V * (1 - $S * $F);
        $K = $V * (1 - $S * (1 - $F));
        //4
        [$R, $G, $B] = match ($I) {
            0 => [$V, $K, $M],
            1 => [$N, $V, $M],
            2 => [$M, $V, $K],
            3 => [$M, $N, $V],
            4 => [$K, $M, $V],
            5, 6 => [$V, $M, $N]
        };
        return [$R, $G, $B];
    }
}
