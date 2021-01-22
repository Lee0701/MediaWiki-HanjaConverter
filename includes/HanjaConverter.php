<?php

require_once('Dictionary.php');
require_once('UserDictionary.php');

class HanjaConverter {
    public static function convert($hanja) {
        $userDictionary = UserDictionary::get();
        
        $chars = preg_split('//u', $hanja, -1, PREG_SPLIT_NO_EMPTY);
        $len = count($chars);
        $result = array();
        
        for($i = 0 ; $i < $len ; ) {
            $found = false;
            for($j = $len - $i ; $j > 0 ; $j--) {
                $key = implode('', array_slice($chars, $i, $j));
                $value = null;
                $grade = null;
                if(isset($userDictionary[$key])) {
                    $value = $userDictionary[$key];
                    $grades = array();
                    for($k = 0 ; $k < $j ; $k++) {
                        $c = $chars[$i + $k];
                        if($c >= "가" and $c <= "힣") continue;
                        array_push($grades, HanjaGrades::gradeOf($c));
                    }
                    $grade = min($grades);
                } else if(isset(Dictionary::$dictionary[$key])) {
                    $value = Dictionary::$dictionary[$key];
                    $value = explode(':', $value);
                    $grade = intval($value[1]);
                    $value = $value[0];
                }
                if($value !== null && $grade !== null) {
                    array_push($result, array($key, $value, $grade));
                    $i += $j;
                    $found = true;
                    break;
                }
            }
            if(!$found) {
                array_push($result, $chars[$i]);
                $i++;
            }
        }
        return $result;
    }
    
    public static function convertWord($word, $unknown='') {
        $arr = array('');
        $result = '';
        foreach(HanjaConverter::convert($word) as $item) {
            if(is_array($item)) {
                if(is_array($arr[array_key_last($arr)])) {
                    $end = array_pop($arr);
                    $end[0] .= $item[0];
                    $end[1] .= $item[1];
                    if($item[2] < $end[2]) $end[2] = $item[2];
                    array_push($arr, $end);
                }
                else array_push($arr, $item);
            } else {
                array_push($arr, $item);
            }
        }
        foreach($arr as $item) {
            if(is_array($item)) {
                $key = $item[0];
                $value = $item[1];
                $grade = $item[2];
                $result .= Ruby::format($key, $value, "grade$grade$unknown");
            } else {
                $result .= $item;
            }
        }
        return $result;
    }
    
}

?>