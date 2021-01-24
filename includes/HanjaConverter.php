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
                if(isset($userDictionary[$key])) {
                    $value = $userDictionary[$key];
                } else if(isset(Dictionary::$dictionary[$key])) {
                    $value = Dictionary::$dictionary[$key];
                }
                if($value !== null) {
                    $grades = array();
                    for($k = 0 ; $k < $j ; $k++) {
                        $c = $chars[$i + $k];
                        if($c >= "가" and $c <= "힣") continue;
                        array_push($grades, HanjaGrades::gradeOf($c));
                    }
                    if(count($grades) > 0) {
                        $grade = min($grades);
                        array_push($result, array($key, $value, $grade));
                        $i += $j;
                        $found = true;
                    }
                    // $value = preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY);
                    // for($k = 0 ; $k < $j ; $k++) {
                    //     $kc = $chars[$i + $k];
                    //     $vc = $value[$k];
                    //     if($kc >= "가" and $kc <= "힣") $grade = 0;
                    //     else $grade = HanjaGrades::gradeOf($kc);
                    //     array_push($result, array($kc, $vc, $grade));
                    // }
                    // $i += $j;
                    // $found = true;
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
    
    public static function convertWord($word) {
        $arr = array('');
        foreach(HanjaConverter::convert($word) as $item) {
            if(is_array($item)) {
                if(is_array($arr[array_key_last($arr)])) {
                    $last = array_pop($arr);
                    $last[0] .= $item[0];
                    $last[1] .= $item[1];
                    if($item[2] < $last[2]) $last[2] = $item[2];
                    array_push($arr, $last);
                }
                else array_push($arr, $item);
                // array_push($arr, $item);
            } else {
                array_push($arr, $item);
            }
        }
        array_shift($arr);
        return $arr;
    }
    
    public static function format($arr, $unknown=false) {
        if($unknown) $unknown = ' unknown';
        else $unknown = '';
        $result = '';
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

    public static function formatWord($word, $unknown=false) {
        return self::format(self::convertWord($word), $unknown);
    }

}

?>