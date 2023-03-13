<?php

require_once('HanjaGradesDictionary.php');

class HanjaGrades {

    public static $grades = array(0, 10, 12, 20, 30, 32, 40, 42, 50, 52, 60, 62, 70, 72, 80);
    
    public static function gradeOf($c) {
        if(array_key_exists($c, HanjaGradesDictionary::$dictionary)) {
            return HanjaGradesDictionary::$dictionary[$c];
        }
        return 0;
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

}

?>