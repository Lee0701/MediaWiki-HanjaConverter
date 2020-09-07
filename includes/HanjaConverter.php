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
                if(isset($userDictionary[$key])) $value = $userDictionary[$key];
                else if(isset(Dictionary::$dictionary[$key])) $value = Dictionary::$dictionary[$key];
                if($value) {
                    $grades = array();
                    for($k = 0 ; $k < iconv_strlen($key) ; $k++)
                        array_push($grades, HanjaGrades::gradeOf(iconv_substr($key, $k, 1)));
                    $grade = min($grades);

                    array_push($result, array($key, $value, $grade));
                    $i += iconv_strlen($key);
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