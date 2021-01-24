<?php

require_once('GradesDictionary.php');

class HanjaGrades {

    public static $grades = array(0, 10, 12, 20, 30, 32, 40, 42, 50, 52, 60, 62, 70, 72, 80);
    
    public static function gradeOf($c) {
        if(array_key_exists($c, GradesDictionary::$dictionary)) {
            return GradesDictionary::$dictionary[$c];
        }
        return 0;
    }

}

?>