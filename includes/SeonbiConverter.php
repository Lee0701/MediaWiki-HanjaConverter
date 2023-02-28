<?php

require_once('Dictionary.php');
require_once('UserDictionary.php');

class SeonbiConverter {

    public static function convertText($text) {
        $postdata = json_encode(
            array(
                'contentType' => 'text/html',
                'content' => $text,
                'quote' => 'CurvedQuotes',
                'cite' => 'AngleQuotes',
                'arrow' => array(
                    'bidirArrow' => true,
                    'doubleArrow' => true
                ),
                'ellipsis' => true,
                'emDash' => true,
                'stop' => 'Horizontal',
                'hanja' => array(
                    'rendering' => 'HanjaInRuby',
                    'reading' => array(
                        'initialSoundLaw' => true,
                        'useDictionaries' => array(
                            'kr-stdict'
                        )
                    )
                )
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
        //TODO: Add host and port options
        $result = file_get_contents('http://seonbi:3800', false, $context);
        $json = json_decode($result, true);
        return $json['content'];
    }

}

?>