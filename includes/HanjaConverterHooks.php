<?php

use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Linker\LinkTarget;

require_once('Ruby.php');
require_once('HanjaGrades.php');
require_once('HanjaConverter.php');

class HanjaConverterHooks {

    public static function onInternalParseBeforeLinks( Parser &$parser, &$text ) {
        if($parser->getTitle()->getNamespace() < 0) return;
        $text = HanjaConverter::format(HanjaConverter::convertText(10, $text, true));
    }

    public static function onHtmlPageLinkRendererBegin(LinkRenderer $linkRenderer, LinkTarget $target, &$text, &$extraAttribs, &$query, &$ret) {
        if(!($target instanceof Title)) return true;
        if(!($text instanceof HtmlArmor)) return true;

        $title = $target->getText();
        $label = HtmlArmor::getHtml($text);

        $result = null;
        if($title == $label) $result = HanjaConverter::convertTitle($title);
        else $result = HanjaConverter::convertText(null, $label, true);
        $result = HanjaConverter::format($result, !$target->isKnown());
        $text = new HtmlArmor($result);
    }
    
    public static function onOutputPageParserOutput( OutputPage $out, ParserOutput $parserOutput ) {
        $title = $parserOutput->getDisplayTitle();
        $result = HanjaConverter::format(HanjaConverter::convertTitle($title));
        $parserOutput->setDisplayTitle($result);
    }

    public static function onBeforePageDisplay(OutputPage $outputPage, Skin $skin) {
        $outputPage->addModuleStyles('ext.HanjaConverter.ruby.hide');
        global $wgUser;
        $unknownLink = $wgUser->getOption('displayRubyForUnknownLink');
        $grade = $wgUser->getOption('displayRubyForGrade');
        $display = $wgUser->getOption('rubyDisplayType');
        $style = "";
        if($unknownLink === '1') {
            $style .= "ruby.hanja.unknown > rt { display: $display; } ruby.hanja.unknown > rp { display: revert; }\n";
        }
        if($grade and $grade != 'none') foreach(HanjaGrades::$grades as $g) {
            $style .= "ruby.hanja.grade$g > rt { display: $display; } ruby.hanja.grade$g > rp { display: revert; }\n";
            if("grade$g" == $grade) break;
        }
        $outputPage->addHeadItem('HanjaConverter.ruby.show', "<style>$style</style>");
    }

    public static function onGetPreferences(User $user, array &$preferences) {
        $preferences['rubyDisplayType'] = [
            'type' => 'select',
            'options' => array(
                wfMessage("tog-HanjaConverter-side")->parse() => 'inline-block',
                wfMessage("tog-HanjaConverter-top")->parse() => 'revert'
            ),
            'label-message' => 'tog-HanjaConverter-rubyDisplayType',
            'section' => 'rendering/HanjaConverter',
        ];
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
            'section' => 'rendering/HanjaConverter',
        ];
        $preferences['displayRubyForUnknownLink'] = [
            'type' => 'toggle',
            'label-message' => 'tog-HanjaConverter-displayRubyForUnknownLink',
            'section' => 'rendering/HanjaConverter',
        ];
    }
    
    public static function onGetDefaultSortkey($title, &$sortkey) {
        $result = "";
        foreach(HanjaConverter::convertText(null, $title, true) as $item) {
            if(is_array($item)) $item = $item[1];
            $result .= $item;
        }
        $sortkey = $result;
        return;
    }

}

?>
