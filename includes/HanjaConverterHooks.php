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
                if($c == '\n' || $c == ' ' || preg_match("/[$hanja_range]/u", $c) == 0) {
                    if($word == '') {
                        $text .= $c;
                    } else {
                        $text .= self::convertEvery(10, $word);
                        $word = $c;
                    }
                } else {
                    $word .= $c;
                }
                continue;
            }
            if($word != '') {
                $text .= self::convertEvery(10, $word);
                $word = '';
            }
            $text .= $c;
        }
        $text .= self::convertEvery(10, $word);
    }

    public static function onHtmlPageLinkRendererBegin(LinkRenderer $linkRenderer, LinkTarget $target, &$text, &$extraAttribs, &$query, &$ret) {
        if(!($target instanceof Title)) return true;
        if(!($text instanceof HtmlArmor)) return true;

        $label = HtmlArmor::getHtml($text);
        $result = HanjaConverter::formatWord($label, !$target->isKnown());
        $text = new HtmlArmor($result);
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

    public static function onOutputPageParserOutput( OutputPage $out, ParserOutput $parserOutput ) {
        $parserOutput->setDisplayTitle(self::convertEvery(10, $parserOutput->getDisplayTitle()));
    }

    public static function onGetPreferences(User $user, array &$preferences) {
        $preferences['rubyDisplayType'] = [
            'type' => 'select',
            'options' => array(
                wfMessage("tog-HanjaConverter-side")->parse() => 'inline-block',
                wfMessage("tog-HanjaConverter-top")->parse() => 'ruby-text'
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
        foreach(HanjaConverter::convert($title) as $item) {
            if(is_array($item)) $item = $item[1];
            if(iconv_strpos($item, "/") !== false) $item = explode("/", $item)[0];
            $result .= $item;
        }
        $sortkey = $result;
        return ;
    }

    private static function convertEvery($n, $word) {
        $chars = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY);
        $arr = array('');
        while(count($chars) > 0) {
            $chunk = array_splice($chars, 0, $n);
            $converted = HanjaConverter::convertWord(implode('', $chunk));
            $last = array_pop($arr);
            $continuing = array_shift($converted);
            if(is_array($last) && is_array($continuing)) {
                $last[0] .= $continuing[0];
                $last[1] .= $continuing[1];
                if($continuing[2] < $last[2]) $last[2] = $continuing[2];
                array_push($arr, $last);
            } else {
                array_push($arr, $last);
                array_unshift($converted, $continuing);
            }
            $arr = array_merge($arr, $converted);
        }
        array_shift($arr);
        return HanjaConverter::format($arr);
    }

}

?>