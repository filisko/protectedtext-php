<?php
namespace Filisko\ProtectedText;

use Blocktrail\CryptoJSAES\CryptoJSAES;

class Helper
{
    public static function encrypt($content, $password)
    {
        return CryptoJSAES::encrypt($content, $password);
    }

    public static function decrypt($content, $password)
    {
        return CryptoJSAES::decrypt($content, $password);
    }

    public static function moveElement(&$array, $a, $b)
    {
        $out = array_splice($array, $a, 1);
        array_splice($array, $b, 0, $out);
    }
    
    public static function removeStringFromEnd($needle, $string)
    {
        return preg_replace('/'. preg_quote($needle, '/') . '$/', '', $string);
    }

    public static function removeLastElementFromArray(array &$array)
    {
        $lastKey = self::getLastArrayKey($array);
        unset($array[$lastKey]);
        return $array;
    }
    
    public static function getLastArrayKey(array $array)
    {
        end($array);
        $key = key($array);
        return $key;
    }
}
