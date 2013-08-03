<?php

class Request
{
    use ActionDispatch\Http\MimeNegotiation;
    use ActionDispatch\Http\Parameters;
    use ActionDispatch\Http\Url;

    private static $LOCALHOST = [ "/^127\.0\.0\.\d{1,3}$/", "/^::1$/", "/^0:0:0:0:0:0:0:1(%.*)?$/" ];

    public static function authorization()
    {
        $headers = self::headers();
        $choice = [ 'Authorization', 'X-http-Authorization', 'Redirect-X-http-Authorization'];

        foreach ($choice as $c)
            if (isset($headers[$c]))
                return $headers[$c];

        return null;
    }

    public static function body()
    {
        return file_get_contents('php://input');
    }

    public static function fullpath()
    {
         return strtok($_SERVER["REQUEST_URI"], '?');
    }

    public static function headers()
    {
        static $headers = null;

        if ($headers !== null)
            return $headers;

        if (function_exists('getallheaders'))
            return $headers = getallheaders();

        foreach ($_SERVER as $key => $value)
        {
            if (substr($key, 0, 5) == 'HTTP_')
            {
                $q = strtolower(str_replace('_', '-', substr($key, 5)));
                $q = implode('-', array_map('ucfirst', explode('-', $q)));
                $headers[$q] = $value;
            }
        }

        return $headers;
    }

    public static function is_xml_http_request()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] == 'xmlhttprequest');
    }

    public static function is_xhr()
    {
        return self::is_xml_http_request();
    }

    public static function is_get()
    {
        return self::request_method() == 'get';
    }

    public static function is_post()
    {
        return self::request_method() == 'post';
    }

    public static function is_head()
    {
        return self::request_method() == 'head';
    }

    public static function is_put()
    {
        return self::request_method() == 'put';
    }

    public static function is_delete()
    {
        return self::request_method() == 'delete';
    }

    public static function is_local()
    {
        foreach (self::$LOCALHOST as $local_ip)
        {
            $ip_hdr = preg_match($local_ip, self::remote_addr());
            $ip_forwarded = preg_match($local_ip, self::remote_ip());

            if ($ip_hdr && $ip_forwarded)
                return true;
        }

        return false;
    }

    public static function method()
    {
        return strtolower($_SERVER['REQUEST_METHOD']);
    }

    public static function query_parameters()
    {
        return $_GET;
    }

    public static function remote_ip()
    {
        if ($forwarded = self::x_forwarded_for() != null)
            return explode(',', $forwarded)[0];
        else if ($client = self::x_client_ip() != null || $client = self::client_id())
            return $client;

        return self::remote_addr();
    }

    public static function request_method()
    {
        if (self::method() == "post")
        {
            if (isset($_POST['_method']))
                return $_POST['_method'];
            return "post";
        }

        return self::method();
    }

    public static function request_parameters()
    {
        return $_POST;
    }

    public static function __callStatic($name, $arguments)
    {
        $env_name = strtoupper($name);

        if (isset($_SERVER[$env_name]))
            return $_SERVER[$env_name];
        else if (isset($_SERVER['HTTP_' . $env_name]))
            return $_SERVER['HTTP_' . $env_name];

        $headers = self::uppercase_headers();
        if (isset($headers[$env_name]))
            return $headers[$env_name];

        return null;
    }

    private static function uppercase_headers()
    {
        static $headers = null;
        if ($headers != null)
            return $headers;

        $hdr = self::headers();
        foreach ($hdr as $key => $val)
        {
            $key = str_replace('-', '_', strtolower($key));
            $headers[$key] = $val;
        }

        return $headers;
    }
}