<?php

use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Linker\LinkTarget;

require_once('Dictionary.php');

class HanjaConverterHooks {
    private static $userDictionarySource = null;
    private static $userDictionary = null;
    public static function onHtmlPageLinkRendererBegin(LinkRenderer $linkRenderer, LinkTarget $target, &$text, &$extraAttribs, &$query, &$ret) {
        if(!($target instanceof Title)) return true;
        if(!($text instanceof HtmlArmor)) return true;
        if($target->isKnown()) return true;

        $label = HtmlArmor::getHtml($text);
        $len = iconv_strlen($label);
        $result = "";

        $newUserDictionarySource = HanjaConverterHooks::readUserDictionary();
        if($newUserDictionarySource !== HanjaConverterHooks::$userDictionarySource) {
            HanjaConverterHooks::$userDictionary = HanjaConverterHooks::parseUserDictionary($newUserDictionarySource);
            HanjaConverterHooks::$userDictionarySource = $newUserDictionarySource;
        }

        for($i = 0 ; $i < $len ; ) {
            $found = false;
            for($j = $len - $i ; $j > 0 ; $j--) {
                $key = iconv_substr($label, $i, $j);
                $value = HanjaConverterHooks::$userDictionary[$key];
                if(!$value) $value = Dictionary::$dictionary[$key];
                if($value) {
                    $result .= HanjaConverterHooks::format($key, $value);
                    $i += iconv_strlen($value);
                    $found = true;
                    break;
                }
            }
            if(!$found) {
                $result .= iconv_substr($label, $i, 1);
                $i++;
            }
        }
        $text = new HtmlArmor($result);
    }
    public static function format($hanja, $reading) {
        return "<ruby><rb>$hanja</rb><rt>$reading</rt><rp>($reading)</rp></ruby>";
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