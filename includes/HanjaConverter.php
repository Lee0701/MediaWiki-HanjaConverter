<?php
namespace HanjaConverter;

use HanjaConverter\Ruby;
use HanjaConverter\HanjaGrades;

abstract class HanjaConverter {

    public static $HALF_VOWELS_FOR_INITIAL_SOUND_LAW = 'ᅣᅤᅧᅨᅭᅲᅵ';
    public static $HANJA_RANGE = '\x{4E00}-\x{62FF}\x{6300}-\x{77FF}\x{7800}-\x{8CFF}\x{8D00}-\x{9FFF}\x{3400}-\x{4DBF}';
    public static $HANGUL_RANGE = '가-힣ㄱ-ㅎㅏ-ㅣ';

    function __construct($config) {
        $this->config = $config;
    }
    
    public abstract function convertText($every_n, $text, $initial=true);

    public static function format($arr, $unknown=false) {
        if($unknown) $unknown = ' unknown';
        else $unknown = '';
        $result = '';
        foreach($arr as $item) {
            if(is_array($item)) {
                $key = $item[0];
                $value = $item[1];
                if($key == $value) {
                    $result .= $key;
                } else {
                    $chars = preg_split('//u', $key, -1, PREG_SPLIT_NO_EMPTY);
                    $grade = HanjaGrades::calculateGrade($chars);
                    $result .= Ruby::format($key, $value, "api grade$grade$unknown");
                }
            } else {
                $result .= $item;
            }
        }
        return $result;
    }

}

?>