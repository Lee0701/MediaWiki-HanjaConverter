<?php

use \MediaWiki\Revision\RevisionRecord;
use \MediaWiki\MediaWikiServices;

class UserDictionary {
    public static $USER_DICTIONARY_PAGE_NAME = 'HanjaConverter-UserDictionary';

    private static $userDictionary = null;

    public static function get($force_reload = false) {
        if(self::$userDictionary === null || $force_reload) {
            $source = self::readUserDictionary();
            self::$userDictionary = self::parseUserDictionary($source);
        }
        return self::$userDictionary;
    }

    public static function readUserDictionary() {
        $title = Title::newFromText(self::$USER_DICTIONARY_PAGE_NAME, NS_MEDIAWIKI);
        $wikipage = MediaWikiServices::getInstance()->getWikiPageFactory()->newFromTitle($title);
        $content = ContentHandler::getContentText($wikipage->getContent(RevisionRecord::RAW));
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