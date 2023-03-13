<?php

require_once('InternalDictionary.php');
require_once('UserDictionary.php');

class HanjaConverter {

    private static $HALF_VOWELS_FOR_INITIAL_SOUND_LAW = 'ᅣᅤᅧᅨᅭᅲᅵ';
    private static $HANJA_RANGE = '\x{4E00}-\x{62FF}\x{6300}-\x{77FF}\x{7800}-\x{8CFF}\x{8D00}-\x{9FFF}\x{3400}-\x{4DBF}';
    private static $HANGUL_RANGE = '가-힣ㄱ-ㅎㅏ-ㅣ';

    public static function convert($hanja, $initial=false) {
        $hanjaRange = self::$HANJA_RANGE;
        $hangulRange = self::$HANGUL_RANGE;
        $userDictionary = UserDictionary::get();

        $chars = preg_split('//u', $hanja, -1, PREG_SPLIT_NO_EMPTY);
        $len = count($chars);
        $result = array();
        
        for($i = 0 ; $i < $len ; ) {
            $c = $chars[$i];
            $found = false;
            for($j = $len - $i ; $j > 0 ; $j--) {
                $key = implode('', array_slice($chars, $i, $j));
                $value = null;
                if(isset($userDictionary[$key])) {
                    $value = $userDictionary[$key];
                } else if(isset(Dictionary::$dictionary[$key])) {
                    $value = Dictionary::$dictionary[$key];
                    if($initial) {
                        $sounds = preg_split('//u', Normalizer::normalize($value, Normalizer::FORM_D), -1, PREG_SPLIT_NO_EMPTY);
                        if($sounds[0] == 'ᄅ') $sounds[0] = 'ᄂ';
                        if($sounds[0] == 'ᄂ' && strpos(self::$HALF_VOWELS_FOR_INITIAL_SOUND_LAW, $sounds[1]) !== false) $sounds[0] = 'ᄋ';
                        $composed = $value = Normalizer::normalize(implode('', $sounds), Normalizer::FORM_C);
                        if($value != $composed) $value = $composed;
                    }
                }
                if($value !== null) {
                    $value_chars = preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY);
                    // value string that is different from original key (is not hanja?)
                    $different = '';
                    for($k = 0 ; $k < $j ; $k++) {
                        if($value_chars[$k] == $chars[$i + $k]) {
                            if($different != '') {
                                array_push($result, array(implode('', array_slice($chars, $i, $k)), $different, self::calculateGrade($chars, $i, $j)));
                            }
                            array_push($result, $value_chars[$k]);
                            $different = '';
                        } else {
                            $different .= $value_chars[$k];
                        }
                    }
                    
                    if($different != '') {
                        array_push($result, array(implode('', array_slice($chars, $i, $k)), $different, self::calculateGrade($chars, $i, $j)));
                    }
                    
                    $i += $j;
                    $found = true;
                    break;
                }
            }
            if(!$found) {
                array_push($result, $chars[$i]);
                $i++;
            }
            $is_word = preg_match("/[0-9A-Za-z$hanjaRange]/u", $c) !== 0;
            if($is_word) $initial = false;
            else $initial = true;
        }
        return $result;
    }

    private static function calculateGrade($chars, $offset=0, $len=-1) {
        $hangulRange = self::$HANGUL_RANGE;
        if($len == -1) $len = count($chars);
        $grades = array();
        for($k = 0 ; $k < $len ; $k++) {
            $c = $chars[$offset + $k];
            if(preg_match("/$hangulRange/u", $c) !== 0) continue;
            array_push($grades, HanjaGrades::gradeOf($c));
        }
        if(count($grades) > 0) $grade = min($grades);
        else $grade = 0;
        return $grade;
    }
    
    public static function convertWord($word, $initial=false) {
        $arr = array('');
        foreach(self::convert($word, $initial) as $item) {
            if(is_array($item)) {
                if(is_array($arr[array_key_last($arr)])) {
                    $last = array_pop($arr);
                    $last[0] .= $item[0];
                    $last[1] .= $item[1];
                    if($item[2] < $last[2]) $last[2] = $item[2];
                    array_push($arr, $last);
                }
                else array_push($arr, $item);
            } else {
                array_push($arr, $item);
            }
        }
        array_shift($arr);
        return $arr;
    }
    
    public static function convertEvery($n, $word, $initial=false) {
        $chars = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY);
        $arr = array('');
        while(count($chars) > 0) {
            $chunk = null;
            if($n == null) $chunk = array_splice($chars, 0);
            else $chunk = array_splice($chars, 0, $n);
            $converted = self::convertWord(implode('', $chunk), $initial);
            $initial = false;
            $last = array_pop($arr);
            $continuing = array_shift($converted);
            if(is_array($last) && is_array($continuing)) {
                $last[0] .= $continuing[0];
                $last[1] .= $continuing[1];
                if($continuing[2] < $last[2]) $last[2] = $continuing[2];
                array_push($arr, $last);
            } else {
                array_push($arr, $last);
                array_unshift($converted, $continuing);
            }
            $arr = array_merge($arr, $converted);
        }
        return $arr;
    }

    public static function convertText($every_n, $text, $initial=true) {
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
        $arr = array();
        $len = count($chars);
        $brackets = 0;
        $word = '';
        for( $i = 0 ; $i < $len ; $i++ ) {
            $c = $chars[$i];
            // Links do not span between lines
            if(preg_match('/\n/u', $c) !== 0) {
                $brackets = 0;
            }
            if($c == ']') {
                $brackets--;
            } else if($c == '[') {
                $brackets++;
            } else if($brackets < 2) {
                $is_whitespace = preg_match("/[\\s]/u", $c) !== 0;
                if($is_whitespace) {
                    if($word == '') {
                        array_push($arr, $c);
                    } else {
                        $arr = array_merge($arr, self::convertEvery($every_n, $word, $initial));
                        $word = '';
                        array_push($arr, $c);
                    }
                    if($is_whitespace) $initial = true;
                    else $initial = false;
                } else {
                    $word .= $c;
                }
                continue;
            }
            if($word != '') {
                $arr = array_merge($arr, self::convertEvery($every_n, $word, $initial));
                $word = '';
            }
            array_push($arr, $c);
        }
        $arr = array_merge($arr, self::convertEvery($every_n, $word, $initial));
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

}

?>