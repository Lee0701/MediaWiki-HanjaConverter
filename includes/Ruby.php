<?php

class Ruby {
    public static function format($hanja, $reading, $class) {
        $classes = "";
        if($class) $classes = "class=\"hanja $class\"";
        return "<ruby $classes><rb>$hanja</rb><rt>$reading</rt><rp>($reading)</rp></ruby>";
    }
}

?>