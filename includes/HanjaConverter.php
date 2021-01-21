<?php

require_once('Dictionary.php');
require_once('UserDictionary.php');

class HanjaConverter {
    public static function convert($hanja) {
        $userDictionary = UserDictionary::get();

        $len = iconv_strlen($hanja);
        $result = array();

        for($i = 0 ; $i < $len ; ) {
            $found = false;
            for($j = $len - $i ; $j > 0 ; $j--) {
                $key = iconv_substr($hanja, $i, $j);
                $value = null;
                $grade = null;
                if(isset($userDictionary[$key])) {
                    $value = $userDictionary[$key];
                    $grades = array();
                    for($k = 0 ; $k < $j ; $k++) {
                        $c = iconv_substr($key, $k, 1);
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
                array_push($result, iconv_substr($hanja, $i, 1));
                $i++;
            }
        }
        return $result;
    }
}

?>