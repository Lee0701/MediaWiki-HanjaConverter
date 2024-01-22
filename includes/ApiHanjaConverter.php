<?php

use MediaWiki\MediaWikiServices;

class ApiHanjaConverter {
    
    public static function convert($text) {
        $apiUrl = self::getConfig()->get('HanjaConverterConversionApiUrl');
        $merge = self::getConfig()->get('HanjaConverterMerge');

        $postdata = json_encode(
            array(
                'text' => $text,
                'merge' => $merge,
                'stringify' => false,
            )
        );
        $opts = array('http' =>
            array(
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => $postdata
            )
        );
        $context = stream_context_create($opts);

        $result = file_get_contents($apiUrl, false, $context);
        $json = json_decode($result, true);
        return $json['result'];
    }

    public static function excludeBrackets($arr) {
        $result = array();
        $brackets = 0;
        foreach($arr as $a) {
            $input = $a[0];
            $output = $a[1];
            // Links do not span between lines
            if(str_contains($input, '\n')) $brackets = 0;
            if(str_contains($input, '[[')) $brackets += 2;
            if(str_contains($input, ']]')) $brackets -= 2;

            if($brackets > 0 && $input != $output) array_push($result, array($input, $input));
            else array_push($result, array($input, $output));
        }
        return $result;
    }

    public static function convertText($every_n, $text, $initial=true) {
        return self::excludeBrackets(self::convert($text));
    }

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

    public static function getConfig() {
        return MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'hanjaconverter' );
    }

}