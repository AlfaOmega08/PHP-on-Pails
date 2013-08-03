<?php

namespace ActionController;

trait RequestForgeryProtection
{
    function protect_from_forgery($options = [])
    {
        $this->prepend_before_action('verify_authenticity_token');
    }

    function verify_authenticity_token()
    {
        if (!$this->request_verified())
        {
            @session_start();
            @session_unset();
            @session_destroy();

            $past = time() - 3600;
            foreach ($_COOKIE as $key => $value)
                setcookie($key, $value, $past, '/');

            die('');
        }
    }

    static function form_authenticity_token()
    {
        static $token = null;
        if ($token !== null)
            return $token;

        if ($token = self::get_cookie('csrf_token', [ 'signed' => true ]))
            return $token;
        else
        {
            $token = bin2hex(fread(fopen('/dev/urandom', 'r'), 32));
            self::cookie('csrf_token', $token, [ 'signed' => true ]);
        }

        return $token;
    }

    function request_verified()
    {
        if (isset($this->params['authenticity_token']))
            $auth = $this->params['authenticity_token'];
        else
            $auth = null;

        return \Request::is_get() ||
            \Request::is_head() ||
            $this->form_authenticity_token() == $auth ||
            $this->form_authenticity_token() == \Request::X_CSRF_Token§();
    }
}