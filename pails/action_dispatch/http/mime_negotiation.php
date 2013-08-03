<?php

namespace ActionDispatch\Http;

trait MimeNegotiation
{
    public static function accepts()
    {
        static $accepts = null;
        if ($accepts !== null)
            return $accepts;

        $header = $headers = self::headers();
        if (!isset($header['Accept']) || empty($header['Accept']))
            return [ self::content_mime_type() ];
        else
            return Mime\Type::parse($header['Accept']);
    }

    public static function content_mime_type()
    {
        static $mime = null;
        if ($mime !== null)
            return $mime;

        $headers = self::headers();
        if (preg_match('/^([^,\;]*)/', $headers['Content-Type'], $matches))
            $mime = Mime\Type::lookup(strtolower(trim($matches[1])));

        return $mime;
    }

    public static function content_type()
    {
        return (string) self::content_mime_type();
    }

    public static function get_format()
    {
        return self::formats()[0];
    }

    public static function formats()
    {
        static $formats = null;
        if ($formats !== null)
            return $formats;

        if (self::parameters()['format'])
            $formats = [ Mime\Type::extension_lookup(self::parameters()['format']) ];
        else if (self::valid_accept_header())
            $formats = self::accepts();
        else if (self::is_xhr())
            $formats = [ Mime\Tyep::lookup("text/javascript") ];
        else
            $formats = [ Mime\Type::lookup("text/html") ];

        return $formats;
    }

    public static function format($format)
    {
        self::parameters()['format'] = $format;
    }

    public static function valid_accept_header()
    {
        $BROWSER_LIKE_ACCEPTS = '/,\s*\*\/\*|\*\/\*\s*,/';
        if (self::is_xhr())
            if (self::accept() || self::content_mime_type())
                return true;
        else if (self::accept())
            if (preg_match($BROWSER_LIKE_ACCEPTS, self::accept()))
                return true;

        return false;
    }
}