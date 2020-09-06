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
        $unknown = '';
        if(!$target->isKnown()) $unknown = ' unknown';

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

                    $result .= Ruby::format($key, $value, "grade$grade$unknown");
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
        $outputPage->addModuleStyles('ext.HanjaConverter.ruby.hide');
        global $wgUser;
        $unknownLink = $wgUser->getOption('displayRubyForUnknownLink');
        $grade = $wgUser->getOption('displayRubyForGrade');
        $style = "";
        if($unknownLink === '1') {
            $style .= "ruby.hanja.unknown > rt, ruby.hanja.unknown > rp { display: revert; }\n";
        }
        if($grade) foreach(HanjaGrades::$grades as $g) {
            if("grade$g" == $grade) break;
            $style .= "ruby.hanja.grade$g > rt, ruby.hanja.grade$g > rp { display: revert; }\n";
        }
        $outputPage->addHeadItem('HanjaConverter.ruby.show', "<style>$style</style>");
    }

    public static function onGetPreferences(User $user, array &$preferences) {
        $preferences['displayRubyForUnknownLink'] = [
            'type' => 'toggle',
            'label-message' => 'tog-HanjaConverter-displayRubyForUnknownLink',
            'section' => 'rendering',
        ];
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
    }
}

?>