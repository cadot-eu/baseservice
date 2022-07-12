<?php

namespace App\Service\base;


class ArrayHelper
{
    /**
     * Determines if an array is associative.
     *
     * An array is "associative" if it doesn't have sequential numerical keys beginning with zero.
     *
     * @param  array  $array
     * @return bool
     */
    public static function isAssoc(array $array)
    {
        $keys = array_keys($array);

        return array_keys($keys) !== $keys;
    }
    /**
     * move a element in other position 
     * 
     * @param array The array you want to move an element in.
     * @param a The index of the element to move.
     * @param b The index of the element to move.
     */
    public static function moveElement($array, $a, $b)
    {
        $out = array_splice($array, $a, 1);
        array_splice($array, $b, 0, $out);
        return $array;
    }
}
