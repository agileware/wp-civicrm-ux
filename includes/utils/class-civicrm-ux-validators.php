<?php

class Civicrm_Ux_Validators
{
    public static function validateCssColor($color): ?string
    {
        $color = preg_replace('{^ ([a-f0-9]{3,4}|[a-f0-9]{6}|[a-f0-9]{8}) $}xi', '#$0', $color);

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

	public static function validateAPIFieldName($field, $key = null): ?string
	{
		/** API Field names:
		 *   - must contain only alphanumeric characters, dash (-), underscore(_), dot(.) or colon (:)
		 *   - may not start or end with a dot dash or colon
		 */
		$valid = preg_match('{  
		                        ^ (?! [.:-] )    # Exclude disallowed prefix 
		                        [[:alnum:]._:-]+ # Sequence of valid characters
		                        (?<! [.:-]) $    # Exclude disallowed suffix
		                         }xi', $field, $matches);

		if ( ! $valid ) {
			error_log( sprintf( 
				( ! $key ) ? __( '%1$s: Invalid field key given' ) : __( '%1$s: Invalid key given for "%2$s"' ),
				'ux_event_fullcalendar', $key ) );
			return null;
		}

		return $matches[0];
	}
}
