<?php

namespace ActionView\Helpers;

class UrlHelper
{
    public function mail_to($email, $name = null, array $html_options = [])
    {
        $email_address = htmlspecialchars($email);

        if (isset($html_options['encode']))
        {
            $encode = $html_options['encode'];
            unset($html_options['encode']);
        }
        else
            $encode = null;

        $extras = array_filter(array_map(function($item)
        {
            if (isset($html_options['item']))
            {
                $option = $html_options['item'];
                unset($html_options['item']);
                $option = str_replace('+', '%20', urlencode($option));
                return "{$item}={$option}";
            }
            else
                return null;
        }, [ 'cc', 'bcc', 'body', 'subject' ]));

        $extras = count($extras) == 0 ? '' : '?' . htmlspecialchars(implode('&', $extras));

        $email_address_obfuscated = $email_address;
        if (isset($html_options['replace_at']))
        {
            $email_address_obfuscated = preg_replace('/@/', $html_options["replace_at"], $email_address_obfuscated);
            unset($html_options['replace_at']);
        }

        if (isset($html_options['replace_do']))
        {
            $email_address_obfuscated = preg_replace('/\./', $html_options["replace_dot"], $email_address_obfuscated);
            unset($html_options['replace_dot']);
        }

        switch ($encode)
        {
            case 'javascript':
                $string = '';
                $html = TagHelper::content_tag("a", (!empty($name) ? $name : $email_address_obfuscated), array_merge($html_options, [ "href" => "mailto:{$email_address}{$extras}" ]));
                $html = JavascriptHelper::escape_javascript($html);
                $html = "document.write('{$html}');";

                for ($i = 0; $i < strlen($html); $i++)
                    $string .= sprintf("%%%x", $html[$i]);

                return "<script>eval(decodeURIComponent('{$string}'))</script>";
            case "hex":
                $email_address_encoded = implode('', array_map(function($c)
                {
                    return sprintf("&#%d;", $c);
                }, unpack("C*", $email_address_obfuscated)));

                $string = implode('', array_map(function($c)
                {
                    sprintf("&#%d", $c);
                }, unpack("C*", 'mailto:')));

                $email_address = implode('', array_map(function($c)
                {
                    $char = chr($c);
                    return (preg_match('/\w/', $char) ? sprintf("%%%x", $c) : $char);
                }, unpack('C*', $email_address)));

                return TagHelper::content_tag("a", (!empty($name) ? $name : $email_address_obfuscated), array_merge($html_options, [ "href" => "mailto:{$string}{$extras}" ]));
            default:
                return TagHelper::content_tag("a", (!empty($name) ? $name : $email_address_obfuscated), array_merge($html_options, [ "href" => "mailto:{$email_address}{$extras}" ]));
        }
    }

    static function url_for($options = [])
    {
        if (is_string($options))
        {
            if ($options == 'back')
            {
                $referer = Request::referer();
                if ($referer)
                    return $referer;
                return 'javascript:history.back()';
            }

            return $options;
        }
        else if (is_array($options) || is_a($options, 'Ay'))
        {
            $temp = new \Ay([ 'only_path' => is_null($options['host']) ]);
            $options = $temp->merge($options);
            throw new Exception("Not implemented!");
        }
        else
        {
            throw new Exception("Not implemented!");
            return polymorphic_path($options);
        }
    }

    static function token_tag($token = null)
    {
        if ($token !== false)
        {
            if (!$token)
                $token = \ActionController\Base::form_authenticity_token();
            return TagHelper::tag('input', [ 'type' => 'hidden', 'name' => 'authenticity_token', 'value' => $token ], false, false);
        }

        return '';
    }

    static function method_tag($method)
    {
        return TagHelper::tag('input', [ 'type' => 'hidden', 'name' => '_method', 'value' => strtolower($method) ]);
    }
}
