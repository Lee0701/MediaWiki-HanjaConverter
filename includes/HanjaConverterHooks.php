<?php

use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Linker\LinkTarget;

require_once('Ruby.php');
require_once('HanjaGrades.php');
require_once('HanjaConverter.php');

class HanjaConverterHooks {

    public static function onInternalParseBeforeLinks( Parser &$parser, &$text ) {
        if($parser->getTitle()->getNamespace() < 0) return;
        $hanja_range = '\x{4E00}-\x{62FF}\x{6300}-\x{77FF}\x{7800}-\x{8CFF}\x{8D00}-\x{9FFF}\x{3400}-\x{4DBF}';
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
        $text = '';
        $len = count($chars);
        $brackets = 0;
        $word = '';
        for( $i = 0 ; $i < $len ; $i++ ) {
            $c = $chars[$i];
            if($c == ']') {
                $brackets--;
            } else if($c == '[') {
                $brackets++;
            } else if($brackets < 2) {
                if($c == '\n' || $c == ' ' || preg_match("/[$hanja_range]/u", $c) == 0 || iconv_strlen($word) >= 5) {
                    if($word == '') {
                        $text .= $c;
                    } else {
                        $text .= self::convertWord($word);
                        $text .= $c;
                        $word = '';
                    }
                } else {
                    $word .= $c;
                }
                continue;
            }
            $text .= self::convertWord($word);
            $word = '';
            $text .= $c;
        }
        $text .= self::convertWord($word);
    }

    public static function onHtmlPageLinkRendererBegin(LinkRenderer $linkRenderer, LinkTarget $target, &$text, &$extraAttribs, &$query, &$ret) {
        if(!($target instanceof Title)) return true;
        if(!($text instanceof HtmlArmor)) return true;
        $unknown = '';
        if(!$target->isKnown()) $unknown = ' unknown';

        $label = HtmlArmor::getHtml($text);
        $result = self::convertWord($label, $unknown);
        $text = new HtmlArmor($result);
    }
    
    public static function onBeforePageDisplay(OutputPage $outputPage, Skin $skin) {
        $outputPage->addModuleStyles('ext.HanjaConverter.ruby.hide');
        global $wgUser;
        $unknownLink = $wgUser->getOption('displayRubyForUnknownLink');
        $grade = $wgUser->getOption('displayRubyForGrade');
        $style = "";
        if($unknownLink === '1') {
            $style .= "ruby.hanja.unknown > rt, ruby.hanja.unknown > rp { display: revert; }\n";
        }
        if($grade and $grade != 'none') foreach(HanjaGrades::$grades as $g) {
            $style .= "ruby.hanja.grade$g > rt, ruby.hanja.grade$g > rp { display: revert; }\n";
            if("grade$g" == $grade) break;
        }
        $outputPage->addHeadItem('HanjaConverter.ruby.show', "<style>$style</style>");
    }

    public static function onGetPreferences(User $user, array &$preferences) {
        $options = array();
        $options[wfMessage("tog-HanjaConverter-none")->parse()] = "none";
        foreach(HanjaGrades::$grades as $grade) {
            $options[wfMessage("tog-HanjaConverter-grade$grade")->parse()] = "grade$grade";
        }
        $preferences['displayRubyForGrade'] = [
            'type' => 'select',
            'label-message' => 'tog-HanjaConverter-displayRubyForGrade',
            'options' => $options,
            'default' => $user->getOption('displayRubyForGrade', 'grade80'),
            'section' => 'rendering',
        ];
        $preferences['displayRubyForUnknownLink'] = [
            'type' => 'toggle',
            'label-message' => 'tog-HanjaConverter-displayRubyForUnknownLink',
            'section' => 'rendering',
        ];
    }
    
    public static function onGetDefaultSortkey($title, &$sortkey) {
        $result = "";
        foreach(HanjaConverter::convert($title) as $item) {
            if(is_array($item)) $item = $item[1];
            if(iconv_strpos($item, "/") !== false) $item = explode("/", $item)[0];
            $result .= $item;
        }
        $sortkey = $result;
        return ;
    }

    private static function convertWord($word, $unknown='') {
        $result = "";
        foreach(HanjaConverter::convert($word) as $item) {
            if(is_array($item)) {
                $key = $item[0];
                $value = $item[1];
                $grade = $item[2];
                $result .= Ruby::format($key, $value, "grade$grade$unknown");
            } else {
                $result .= $item;
            }
        }
        return $result;
    }

}

?>