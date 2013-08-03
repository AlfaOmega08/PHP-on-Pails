<?php

namespace ActionView\Helpers;

class TagHelper
{
    private static $PRE_CONTENT_STRINGS = [
        'textarea' => "\n",
    ];

    public static function content_tag($name, $content_or_options_with_block = null, $options = [], $escape = true, callable $block = null)
    {
        if ($block !== null)
        {
            if (is_array($content_or_options_with_block))
                $options = $content_or_options_with_block;

            return self::content_tag_string($name, capture($block()), $options, $escape);
        }
        else
            return self::content_tag_string($name, $content_or_options_with_block, $options, $escape);
    }

    public static function content_tag_string($name, $content, $options = [], $escape = true)
    {
        $tag_options = !empty($options) ? self::tag_options($options, $escape) : "";

        $content = $escape ? htmlspecialchars($content) : $content;
        $ret = "<{$name}{$tag_options}>";
        if (isset(self::$PRE_CONTENT_STRINGS[$name]))
            $ret .= self::$PRE_CONTENT_STRINGS[$name];

        return $ret . "{$content}</{$name}>";
    }

    public static function tag($name, $options = [], $open = false, $escape = true)
    {
        $str = "<{$name}";

        if (count($options))
            $str .= self::tag_options($options, $escape);

        $str .= $open ? '>' : ' />';

        return $str;
    }

    private static $BOOLEAN_ATTRIBUTES = [
        'disabled', 'readonly', 'multiple', 'checked', 'autobuffer',
        'autoplay', 'controls', 'loop', 'selected', 'hidden', 'scoped', 'async',
        'defer', 'reversed', 'ismap', 'seemless', 'muted', 'required',
        'autofocus', 'novalidate', 'formnovalidate', 'open', 'pubdate'
    ];

    public static function tag_options($options, $escape = true)
    {
        if (!count($options))
            return '';

        $attributes = [];
        foreach ($options as $key => $value)
        {
            if ($key == 'data' && is_array($value))
            {
                foreach ($value as $k => $v)
                {
                    if (!is_string($v) && is_int($v))
                        $v = json_encode($v);

                    if ($escape)
                        $v = htmlspecialchars($v);

                    $attributes[] = 'data-' . dasherize($k) . "='{$v}'";
                }
            }
            else if (isset(self::$BOOLEAN_ATTRIBUTES[$key]))
            {
                if ($value)
                    $attributes[] = "{$key}='{$key}'";
            }
            else if (!is_null($value))
            {
                $final_value = is_array($value) ? implode(" ", $value) : $value;
                if ($escape)
                    $final_value = htmlspecialchars($final_value);
                $attributes[] = "{$key}='{$final_value}'";
            }
        }

        if (count($attributes))
        {
            sort($attributes);
            return " " . implode(' ', $attributes);
        }
        return "";
    }
}