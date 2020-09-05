<?php

use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Linker\LinkTarget;

require_once('Dictionary.php');

class HanjaConverterHooks {
    public static function onHtmlPageLinkRendererBegin(LinkRenderer $linkRenderer, LinkTarget $target, &$text, &$extraAttribs, &$query, &$ret) {
        if($target->isKnown()) return;
        $label = HtmlArmor::getHtml($text);
        $len = iconv_strlen($label);
        $result = "";
        for($i = 0 ; $i < $len ; ) {
            $found = false;
            for($j = $len - $i ; $j > 0 ; $j--) {
                $key = iconv_substr($label, $i, $j);
                $value = Dictionary::$dictionary[$key];
                if($value) {
                    $result .= HanjaConverterHooks::format($key, $value);
                    $i += iconv_strlen($key);
                    $found = true;
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
}

?>