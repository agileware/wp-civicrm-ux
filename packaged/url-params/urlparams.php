<?php
/*
Plugin Name: URL Params
Plugin URI: http://asandia.com/wordpress-plugins/urlparams/
Description: Short Code to grab any URL Parameter
Version: 2.2
Author: Jeremy B. Shapiro
Author URI: http://www.asandia.com/
*/

// Disallow direct access
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/*
URL Params (Wordpress Plugin)
Copyright (C) 2011-2019 Jeremy Shapiro

*/

//tell wordpress to register the shortcodes
add_shortcode("urlparam", "urlparam");
add_shortcode("ifurlparam", "ifurlparam");

function urlparam($atts, $content) {
    $defaults = array(
        'param'          => '',
        'default'        => '',
        'dateformat'	 => '',
        'attr'           => '',
        'htmltag'        => false,
    );

    # we used to use shortcode_atts(), but that would nuke an extra attributes that we don't know about but want. array_merge() keeps them all.
    $atts = array_merge($defaults, $atts);

    // Sanitize inputs
    $atts['param'] = sanitize_text_field($atts['param']);
    $atts['default'] = sanitize_text_field($atts['default']);
    $atts['dateformat'] = sanitize_text_field($atts['dateformat']);
    $atts['attr'] = sanitize_text_field($atts['attr']);
    $atts['htmltag'] = sanitize_text_field($atts['htmltag']);

    $params = preg_split('/\,\s*/',$atts['param']);

    $return = false;

    foreach($params as $param)
    {
        if(!$return and ($rawtext = $_REQUEST[$param] ?? ''))
        {
            // Sanitize the request parameter value
            $rawtext = sanitize_text_field($rawtext);
            
            if(($atts['dateformat'] != '') && strtotime($rawtext))
            {
                $return = date($atts['dateformat'], strtotime($rawtext));
            } else {
                $return = esc_html($rawtext);
            }
        }
    }

    if(!$return) {
        $return = $atts['default'];
    }

    if($atts['attr']) {
        $return = ' ' . $atts['attr'] . '="' . $return . '" ';

        if($atts['htmltag']) {
            $tagname = $atts['htmltag'];

            foreach(array_keys($defaults) as $key) {
                unset($atts[$key]);
            }

            $otheratts = "";
            foreach($atts as $key => $val) {
                $otheratts .= " $key=\"$val\"";
            }

            $return = "<$tagname $otheratts $return".($content ? ">$content</$tagname>" : "/>");
        }
    }

    return $return;
}

/*
 * If 'param' is found and 'is' is set, compare the two and display the contact if they match
 * If 'param' is found and 'is' isn't set, display the content between the tags
 * If 'param' is not found and 'empty' is set, display the content between the tags
 *
 */
function ifurlparam($atts, $content) {
    $atts = shortcode_atts(array(
        'param'           => '',
        'empty'          => false,
        'is'            => false,
    ), $atts);

    // Sanitize inputs
    $atts['param'] = sanitize_text_field($atts['param']);
    $atts['empty'] = rest_sanitize_boolean($atts['empty']);
    $atts['is'] = sanitize_text_field($atts['is']);

    $params = preg_split('/\,\s*/',$atts['param']);

    foreach($params as $param)
    {
        if(isset($_REQUEST[$param]) && $_REQUEST[$param])
        {
            // Sanitize the request parameter value
            $request_value = sanitize_text_field($_REQUEST[$param]);
            
            if($atts['empty'])
            {
                return '';
            } elseif(!$atts['is'] or ($request_value == $atts['is'])) {
                return do_shortcode($content);
            }
        }
    }

    if ($atts['empty'])
    {
        return do_shortcode($content);
    }

    return '';
}

?>
