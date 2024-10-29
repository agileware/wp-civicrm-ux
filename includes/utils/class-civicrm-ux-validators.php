<?php

class Civicrm_Ux_Validators
{
    public static function validateCssColor($color): ?string
    {
        $valid = preg_match('{
            ^ \s* (
                # Hexadecimal Colors: #RGB, #RRGGBB, #RRGGBBAA
                \#([a-f0-9]{3,4}|[a-f0-9]{6}|[a-f0-9]{8})\b
            
              |
            
              # RGB and RGBA Colors: rgb(r, g, b) | rgb(r g b / a) or rgba(r, g, b, a)
              rgb(a?)\(
                        \s*([0-9]{1,3}%?\s*,?\s+){2}[0-9]{1,3}%?
                (?:\s*/\s*(0|1|0?\.\d+))?
                \s*\)
            
              |
            
              # HSL and HSLA Colors: hsl(h, s, l) | hsl(h s l / a) or hsla(h, s, l, a)
              hsl(a?)\(
                \s*\d{1,3}(deg|grad|rad|turn)?\s*,?\s+[0-9]{1,3}%\s*,?\s+[0-9]{1,3}%
                (?:\s*/\s*(0|1|0?\.\d+))?
                \s*\)
            
              |
            
              # Simplified Named Colors: 3 to 20 alphabetic characters
              [a-z]{3,20}
              
              |
              
              # CSS custom properties
              -- (?: [a-z]+ - )* [a-z]+
            )
            \s*
            $ }xi',
            $color,
        $matches);
        return $valid ? $matches[1] : null;
    }
}