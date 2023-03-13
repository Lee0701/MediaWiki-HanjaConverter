<?php

use MediaWiki\MediaWikiServices;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Linker\LinkTarget;

use HanjaConverter\HanjaConverter;
use HanjaConverter\InternalHanjaConverter;
use HanjaConverter\ApiHanjaConverter;
use HanjaConverter\HanjaGrades;

class HanjaConverterHooks {

    private static $hanjaConverter = null;

    public static function onParserFirstCallInit( Parser $parser ) {
        $parser->setHook('noruby', [self::class, 'noRubyTag']);
        // A stack to store levels of ruby/noruby
        $parser->noruby = array();

        $config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'hanjaconverter' );
        $engineType = $config->get('HanjaConverterConversionEngine');
        if($engineType == 'internal') self::$hanjaConverter = new InternalHanjaConverter($config);
        else if($engineType == 'api') self::$hanjaConverter = new ApiHanjaConverter($config);
    }

    public static function noRubyTag( $input, array $args, Parser $parser, PPFrame $frame ) {
        $input = self::addNoRubyToLinks($input);
        array_push($parser->noruby, true);
        $output = $parser->recursiveTagParse( $input, $frame );
        array_pop($parser->noruby);
        return $output;
    }

    public static function onInternalParseBeforeLinks( Parser &$parser, &$text ) {
        if($parser->getTitle()->getNamespace() < 0) return;
        if(!end($parser->noruby)) {
            $text = self::$hanjaConverter->format(self::$hanjaConverter->convertText(10, $text, true));
        }
    }

    public static function onHtmlPageLinkRendererBegin( LinkRenderer $linkRenderer, LinkTarget $target, &$text, &$extraAttribs, &$query, &$ret ) {
        if(!($target instanceof Title)) return true;
        if(!($text instanceof HtmlArmor)) return true;

        $label = HtmlArmor::getHtml($text);
        $label = self::$hanjaConverter->format(self::$hanjaConverter->convertText(null, $label, true), !$target->isKnown());
        $text = new HtmlArmor($label);
    }
    
    public static function onBeforePageDisplay( OutputPage $outputPage, Skin $skin ) {
        $outputPage->addModuleStyles('ext.HanjaConverter.ruby.hide');
        $outputPage->addModules('ext.HanjaConverter.noruby');
        if(!isset($_GET['noruby'])) {
            $user = $outputPage->getContext()->getUser();
            $userOptionsLookup = MediaWikiServices::getInstance()->getUserOptionsLookup();
            $unknownLink = $userOptionsLookup->getOption($user, 'displayRubyForUnknownLink');
            $grade = $userOptionsLookup->getOption($user, 'displayRubyForGrade');
            $display = $userOptionsLookup->getOption($user, 'rubyDisplayType');
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
    }

    public static function onOutputPageParserOutput( OutputPage $out, ParserOutput $parserOutput ) {
        $title = $parserOutput->getDisplayTitle();
        $title = self::$hanjaConverter->format(self::$hanjaConverter->convertText(null, $title, true));
        $parserOutput->setDisplayTitle($title);
    }

    public static function onGetPreferences( User $user, array &$preferences ) {
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
            'default' => MediaWikiServices::getInstance()->getUserOptionsLookup()->getOption($user, 'displayRubyForGrade', 'grade80'),
            'section' => 'rendering/HanjaConverter',
        ];
        $preferences['displayRubyForUnknownLink'] = [
            'type' => 'toggle',
            'label-message' => 'tog-HanjaConverter-displayRubyForUnknownLink',
            'section' => 'rendering/HanjaConverter',
        ];
    }
    
    public static function onGetDefaultSortkey( $title, &$sortkey ) {
        $result = "";
        $converted = array($title->getText());
        $converted = self::$hanjaConverter->convertText(null, $title->getText(), true);
        foreach($converted as $item) {
            if(is_array($item)) $item = $item[1];
            $result .= $item;
        }
        $sortkey = $result;
        return;
    }

    public static function addNoRubyToLinks( $text ) {
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
        $len = count($chars);
        $brackets = 0;
        $link = '';
        $result = '';
        for( $i = 0 ; $i < $len ; $i++ ) {
            $c = $chars[$i];
            // Links do not span between lines
            if(preg_match('/\n/u', $c) !== 0) {
                $brackets = 0;
            }
            if($c == ']') {
                $brackets--;
                continue;
            } else if($c == '[') {
                $brackets++;
                continue;
            } else if($brackets == 2) {
                $link .= $c;
                continue;
            }
            if($brackets == 0 && $link != '') {
                $split = preg_split('/\\|/u', $link);
                if(!isset($split[1])) $split[1] = '';
                if($split[1] == '') $split[1] = $split[0];
                $target = $split[0];
                $display = $split[1];
                $result .= "[[$target|<noruby>$display</noruby>]]";
                $link = '';
            }
            $result .= $c;
        }
        return $result;
    }

}

?>
