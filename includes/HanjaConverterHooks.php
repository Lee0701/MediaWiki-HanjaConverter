<?php

use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Linker\LinkTarget;

require_once('Ruby.php');
require_once('Dictionary.php');
require_once('UserDictionary.php');
require_once('HanjaGrades.php');

class HanjaConverterHooks {
    public static function onHtmlPageLinkRendererBegin(LinkRenderer $linkRenderer, LinkTarget $target, &$text, &$extraAttribs, &$query, &$ret) {
        if(!($target instanceof Title)) return true;
        if(!($text instanceof HtmlArmor)) return true;
        // if($target->isKnown()) return true;

        $userDictionary = UserDictionary::get();

        $label = HtmlArmor::getHtml($text);
        $len = iconv_strlen($label);
        $result = "";

        for($i = 0 ; $i < $len ; ) {
            $found = false;
            for($j = $len - $i ; $j > 0 ; $j--) {
                $key = iconv_substr($label, $i, $j);
                $value = $userDictionary[$key];
                if(!$value) $value = Dictionary::$dictionary[$key];
                if($value) {
                    $grades = array();
                    for($k = 0 ; $k < iconv_strlen($key) ; $k++)
                        array_push($grades, HanjaGrades::gradeOf(iconv_substr($key, $k, 1)));
                    $grade = min($grades);

                    $result .= Ruby::format($key, $value, "grade$grade");
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
    
    public static function onBeforePageDisplay(OutputPage $outputPage, Skin $skin) {
        $outputPage->addModuleStyles('ext.HanjaConverter.ruby');
    }
}

?>