<?php

namespace ActiveModel;

trait SecurePassword
{
    private $has_secure_password = false;

    function has_secure_password()
    {
        $this->has_secure_password = true;

        $this->validates_confirmation_of('password');
        $this->validates_presence_of('password_digest');

//        if (isset($this->$attributes_protected_by_default))
//           self::$attributes_protected_by_default[] = 'password_digest';
    }

    function authenticate($password)
    {
        if (function_exists('password_verify'))
        {
            if (password_verify($password, $this->password_digest))
                return $this;
            return false;
        }
        else
        {
            if (crypt($password, $this->password_digest) == $this->password_digest)
                return $this;
            return false;
        }
    }

    function set_password($unencrypted_password)
    {
        if ($this->has_secure_password)
        {
            if (!empty($unencrypted_password))
            {
                if (function_exists('password_hash'))
                    $this->password_digest = password_hash($unencrypted_password, PASSWORD_DEFAULT);
                else
                {
                    $salt = bin2hex(fread(fopen('/dev/urandom', 'r'), 30));
                    $salt = '$2a$07$' . $salt;
                    $digest = crypt($unencrypted_password, $salt);
                    $this->password_digest = $digest;
                }
            }
        }

        $this->row['password'] = $unencrypted_password;
    }
}
