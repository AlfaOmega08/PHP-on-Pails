<?php

namespace ActionDispatch\Http;

trait Url
{
    public static function domain($tld_length = null)
    {
        if (!self::is_named_host(self::host()))
            return null;

        if ($tld_length == null)
            $tld_length = 1;

        $pieces = explode('.', self::host());
        $domain_pieces = array_slice($pieces, -(1 + $tld_length));
        return implode('.', $domain_pieces);
    }

    public static function host()
    {
        return preg_replace('/:\d+$/', '', self::raw_host_with_port());
    }

    public static function host_with_port()
    {
        return self::host() . self::port_string();
    }

    public static function is_standard_port()
    {
        return self::standard_port() == self::port();
    }

    public static function port()
    {
        static $port = null;
        if ($port !== null)
            return $port;

        if (preg_match('/:(\d+)$/', self::raw_host_with_port(), $matches))
            return (int) $matches[1];
        else
            return self::standard_port();
    }

    public static function port_string()
    {
        return self::is_standard_port() ? '' : ':' . self::port();
    }

    public static function protocol()
    {
        if (!isset($_SERVER['HTTPS']))
            return "http://";

        if ($_SERVER['HTTPS'] == 'off')
            return "http://";

        return "https://";
    }

    public static function raw_host_with_port()
    {
        if (($forwarded = self::x_forwarded_host()) !== null)
            return end(preg_split('/,\s?/', $forwarded));
        else
        {
            // Cannot call self::host() - Infinite recursion
            $headers = self::headers();
            if (isset($headers['Host']))
                return $headers['Host'];

            $server = first_valid_in_array($_SERVER, 'SERVER_NAME', 'SERVER_ADDR', 'LOCAL_ADDR');
            $port = self::server_port();
            return "{$server}:{$port}";
        }
    }

    public static function server_port()
    {
        return $_SERVER['SERVER_PORT'];
    }

    public static function standard_port()
    {
        if (self::protocol() == "https://")
            return 443;
        return 80;
    }

    public static function subdomain($tld_length = null)
    {
        return implode('.', self::subdomains($tld_length));
    }

    public static function subdomains($tld_length = null)
    {
        if (!self::is_named_host(self::host()))
            return [];

        if ($tld_length === null)
            $tld_length = 1;

        $parts = explode('.', self::host());
        return array_slice($parts, 0, -($tld_length + 2));
    }

    public static function url()
    {
        return self::protocol() . self::host_with_port() . self::fullpath();
    }

    private static function is_named_host($host)
    {
        return $host !== null && !preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $host);
    }
}