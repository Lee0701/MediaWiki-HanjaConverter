<?php

class UserDictionary {
    private static $userDictionarySource = null;
    private static $userDictionary = null;

    public static function getUserDictionary() {
        $newUserDictionarySource = self::readUserDictionary();
        if($newUserDictionarySource !== self::$userDictionarySource) {
            self::$userDictionary = self::parseUserDictionary($newUserDictionarySource);
            self::$userDictionarySource = $newUserDictionarySource;
        }
        return self::$userDictionary;
    }

    public static function readUserDictionary() {
        $content = ContentHandler::getContentText(WikiPage::factory(
            Title::newFromText('HanjaConverter-UserDictionary', NS_MEDIAWIKI)
        )->getContent(Revision::RAW));
        return $content;
    }

    public static function parseUserDictionary($content) {
        $lines = explode("\n", $content);
        $dictionary = array();
        foreach($lines as $line) {
            if(strpos($line, '#') === 0) continue;
            $item = explode("=>", $line);
            if(count($item) < 2) continue;
            $key = trim($item[0]);
            $value = trim($item[1]);
            $dictionary[$key] = $value;
        }
        return $dictionary;
    }
}

?>