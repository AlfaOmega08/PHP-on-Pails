<?php

namespace ActionView\Helpers;

class JavascriptHelper
{
    private static $JS_ESCAPE_MAP = [
        '\\'    => '\\\\',
        '</'    => '<\/',
        "\r\n"  => '\n',
        "\n"    => '\n',
        "\r"    => '\n',
        '"'     => '\\"',
        "'"     => "\\'"
    ];

    public static function escape_javascript($js)
    {
        if (!empty($js))
        {
            preg_match_all('/(\\|<\/|\r\n|\342\200\250|[\n\r"\'])/u', $js, $matches);
            $matches = $matches[0];

            foreach ($matches as $match)
                $js = str_replace($match, self::$JS_ESCAPE_MAP[$match], $js);

            return $js;
        }

        return '';
    }
}