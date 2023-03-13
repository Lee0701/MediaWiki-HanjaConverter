<?php
namespace HanjaConverter;

use HanjaConverter\HanjaConverter;
use HanjaConverter\UserDictionary;

class ApiHanjaConverter extends HanjaConverter {
    
    private function convert($text) {
        $postdata = json_encode(
            array(
                'text' => $text,
                'group' => true,
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

        $apiUrl = $this->config->get('HanjaConverterConversionApiUrl');
        $result = file_get_contents($apiUrl, false, $context);
        $json = json_decode($result, true);
        return $json['result'];
    }

    private function excludeBrackets($arr) {
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

    public function convertText($every_n, $text, $initial=true) {
        return $this->excludeBrackets($this->convert($text));
    }

}

?>