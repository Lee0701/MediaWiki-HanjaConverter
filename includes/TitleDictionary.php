<?php

class TitleDictionary {
    public static $TITLE_DICTIONARY_PAGE_NAME = 'HanjaRuby';

    public static function get($title) {
        $title = Title::newFromText("$title/" . self::$TITLE_DICTIONARY_PAGE_NAME, NS_MAIN);
        if(!$title->exists()) return null;
        $content = ContentHandler::getContentText(WikiPage::factory($title)->getContent(Revision::RAW));
        return $content;
    }

}

?>