<?php

use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Linker\LinkTarget;

require_once('Ruby.php');
require_once('HanjaGrades.php');
require_once('HanjaConverter.php');

class HanjaConverterHooks {
    public static function onParserFirstCallInit( Parser $parser ) {
        $parser->setHook('noruby', [self::class, 'noRubyTag']);
    }

    public static function noRubyTag( $input, array $args, Parser $parser, PPFrame $frame ) {
        $parser->noruby = true;
        $output = $parser->recursiveTagParse( $input, $frame );
        $parser->noruby = false;
        return $output;
    }

    public static function onInternalParseBeforeLinks( Parser &$parser, &$text ) {
        if($parser->getTitle()->getNamespace() < 0) return;
        if(!isset($parser->noruby)) {
            $text = HanjaConverter::format(HanjaConverter::convertText(10, $text, true));
            $parser->getLinkRenderer()->noruby = false;
        } else {
            $parser->getLinkRenderer()->noruby = true;
        }
    }

    public static function onHtmlPageLinkRendererBegin(LinkRenderer $linkRenderer, LinkTarget $target, &$text, &$extraAttribs, &$query, &$ret) {
        if(!($target instanceof Title)) return true;
        if(!($text instanceof HtmlArmor)) return true;

        if(isset($linkRenderer->noruby) && $linkRenderer->noruby == true) return true;

        $label = HtmlArmor::getHtml($text);
        $result = HanjaConverter::format(HanjaConverter::convertText(null, $label, true), !$target->isKnown());
        $text = new HtmlArmor($result);
    }
    
    public static function onBeforePageDisplay(OutputPage $outputPage, Skin $skin) {
        $outputPage->addModuleStyles('ext.HanjaConverter.ruby.hide');
        $user = $outputPage->getContext()->getUser();
        $unknownLink = $user->getOption('displayRubyForUnknownLink');
        $grade = $user->getOption('displayRubyForGrade');
        $display = $user->getOption('rubyDisplayType');
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

    public static function onOutputPageParserOutput( OutputPage $out, ParserOutput $parserOutput ) {
        $title = HanjaConverter::format(HanjaConverter::convertText(null, $parserOutput->getDisplayTitle(), true));
        $parserOutput->setDisplayTitle($title);
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
        foreach(HanjaConverter::convertText(null, $title->getText(), true) as $item) {
            if(is_array($item)) $item = $item[1];
            $result .= $item;
        }
        $sortkey = $result;
        return;
    }

}

?>
