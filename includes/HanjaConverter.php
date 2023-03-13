<?php

abstract class HanjaConverter {
    public abstract function convertText($every_n, $text, $initial=true);
    public abstract function format($arr, $unknown=false);

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