<?php

use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Linker\LinkTarget;

require_once('Ruby.php');
require_once('Dictionary.php');
require_once('UserDictionary.php');
require_once('HanjaGrades.php');

class HanjaConverterHooks {
    public static function convert($hanja) {
        $userDictionary = UserDictionary::get();

        $len = iconv_strlen($hanja);
        $result = array();

        for($i = 0 ; $i < $len ; ) {
            $found = false;
            for($j = $len - $i ; $j > 0 ; $j--) {
                $key = iconv_substr($hanja, $i, $j);
                $value = null;
                if(isset($userDictionary[$key])) $value = $userDictionary[$key];
                else if(isset(Dictionary::$dictionary[$key])) $value = Dictionary::$dictionary[$key];
                if($value) {
                    $grades = array();
                    for($k = 0 ; $k < iconv_strlen($key) ; $k++)
                        array_push($grades, HanjaGrades::gradeOf(iconv_substr($key, $k, 1)));
                    $grade = min($grades);

                    array_push($result, array($key, $value, $grade));
                    $i += iconv_strlen($key);
                    $found = true;
                    break;
                }
            }
            if(!$found) {
                array_push($result, iconv_substr($hanja, $i, 1));
                $i++;
            }
        }
        return $result;
    }

    public static function onHtmlPageLinkRendererBegin(LinkRenderer $linkRenderer, LinkTarget $target, &$text, &$extraAttribs, &$query, &$ret) {
        if(!($target instanceof Title)) return true;
        if(!($text instanceof HtmlArmor)) return true;
        $unknown = '';
        if(!$target->isKnown()) $unknown = ' unknown';

        $label = HtmlArmor::getHtml($text);
        $result = "";
        foreach(self::convert($label) as $item) {
            if(is_array($item)) {
                $key = $item[0];
                $value = $item[1];
                $grade = $item[2];
                $result .= Ruby::format($key, $value, "grade$grade$unknown");
            } else {
                $result .= $item;
            }
        }
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
        if($grade) foreach(HanjaGrades::$grades as $g) {
            $style .= "ruby.hanja.grade$g > rt, ruby.hanja.grade$g > rp { display: revert; }\n";
            if("grade$g" == $grade) break;
        }
        $outputPage->addHeadItem('HanjaConverter.ruby.show', "<style>$style</style>");
    }

    public static function onGetPreferences(User $user, array &$preferences) {
        $options = array();
        foreach(HanjaGrades::$grades as $grade) {
            $options[wfMessage("tog-HanjaConverter-grade$grade")->parse()] = "grade$grade";
        }
        $preferences['displayRubyForGrade'] = [
            'type' => 'radio',
            'label-message' => 'tog-HanjaConverter-displayRubyForGrade',
            'options' => $options,
            'default' => $user->getOption('displayRubyForGrade', 'grade0'),
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
        foreach(self::convert($title) as $item) {
            if(is_array($item)) $item = $item[1];
            if(iconv_strpos($item, "/") !== false) $item = explode("/", $item)[0];
            $result .= $item;
        }
        $sortkey = $result;
        return ;
    }
}

?>