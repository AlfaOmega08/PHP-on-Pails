<?php

namespace ActionView\Helpers;

class FormHelper
{
    private $object_name;
    private $real_name;
    private $multipart;

    static function form_tag($url_for_options = [], $options = [], $block = null)
    {
        $html_options = self::html_options_for_form($url_for_options, $options);
        $extra_tags = self::extra_tags_for_form($html_options);
        return TagHelper::tag('form', $html_options, true) . $extra_tags;
    }

    public function form_for($record, $options, $form_definition)
    {
        if (!isset($options['html']))
            $options['html'] = [];

        if (is_string($record))
        {
            $this->object_name = $record;
            $object = null;
        }
        else
        {
            if (is_array($record))
                $object = end($record);
            else
                $object = $record;

            $this->object_name = isset($options['as']) ? $options['as'] : underscore(get_class($record));
            $this->real_name = underscore(get_class($record));
        }

        $this->object = $object;

        ob_start();

        $my_html_options = [
            'accept-charset' => 'UTF-8',
            'method' => 'post',
        ];

        if ($object->is_new_record())
            $my_html_options['action'] = '/' . $this->object_name;
        else
            $my_html_options['action'] = '/' . $this->object_name . '/' . $object->to_param();

        $options['html'] = array_merge($my_html_options, $options['html']);

        if (array_key_exists('remote', $options))
        {
            $options['html']['remote'] = $options['remote'];
            unset($options['remote']);
        }
        if (array_key_exists('method', $options))
        {
            $options['html']['method'] = $options['method'];
            unset($options['method']);
        }
        else
        {
            if ($object->is_new_record())
                $options['html']['method'] = 'post';
            else
                $options['html']['method'] = 'put';
        }
        if (array_key_exists('authenticity_token', $options))
        {
            $options['html']['authenticity_token'] = $options['authenticity_token'];
            unset($options['authenticity_token']);
        }
        else
            $options['html']['authenticity_token'] = null;

        ob_start();
        $form_definition($this);
        $form_def = ob_get_contents();
        ob_end_clean();

        if ($this->multipart)
            $options['html']['enctype'] = 'multipart/form-data';

        echo self::form_tag($my_html_options['action'], $options['html']);
        echo $form_def;
        echo "</form>";

        $form = ob_get_contents();
        ob_end_clean();

        return $form;
    }

    public function label($method, $content = "", array $options = [])
    {
        if (empty($content))
            $content = humanize($method);
        $options['for'] = "{$this->real_name}_{$method}";
        return (new TagHelper)->tag('label', $options, false) . $content . "</label>";
    }

    public function text_field($method, array $options = [])
    {
        $myoptions = [];
        $myoptions['name'] = "{$this->real_name}[{$method}]";
        $myoptions['id'] = "{$this->real_name}_{$method}";
        if ($this->object)
            $myoptions['value'] = $this->object->$method;
        $myoptions['type'] = "text";
        return (new TagHelper)->tag('input', array_merge($myoptions, $options));
    }

    public function hidden_field($method, array $options = [])
    {
        $myoptions = [];
        $myoptions['name'] = "{$this->real_name}[{$method}]";
        $myoptions['id'] = "{$this->real_name}_{$method}";
        if ($this->object)
            $myoptions['value'] = $this->object->$method;
        $myoptions['type'] = "hidden";
        return (new TagHelper)->tag('input', array_merge($myoptions, $options));
    }

    public function password_field($method, array $options = [])
    {
        $myoptions = [];
        $myoptions['name'] = "{$this->real_name}[{$method}]";
        $myoptions['id'] = "{$this->real_name}_{$method}";
        $myoptions['type'] = "password";
        return (new TagHelper)->tag('input', array_merge($myoptions, $options));
    }

    public function text_area($method, array $options = [])
    {
        $myoptions = [];
        $myoptions['name'] = "{$this->real_name}[{$method}]";
        $myoptions['id'] = "{$this->real_name}_{$method}";

        $ret = (new TagHelper)->tag('textarea', array_merge($myoptions, $options), false);
        if ($this->object)
            $ret .= $this->object->$method;
        return $ret . "</textarea>";
    }

    public function file_field($method, array $options = [])
    {
        $myoptions = [];
        $myoptions['name'] = "{$this->real_name}[{$method}]";
        $myoptions['id'] = "{$this->real_name}_{$method}";
        $options['type'] = 'file';
        $this->multipart = true;
        return (new TagHelper)->tag('input', array_merge($myoptions, $options));
    }

    public function submit($value = null, array $options = [])
    {
        if (is_array($value))
            list($value, $options) = array(null, $value);

        if ($value === null)
            $value = $this->submit_default_value();

        return $this->submit_tag($value, $options);
    }

    public function submit_tag($value = "Save changes", $options = [])
    {
        if (isset($options['disable_with']))
        {
            $options["data-disable-with"] = $options['disable_with'];
            unset($options['disable_with']);
        }

        if (isset($options["confirm"]))
        {
            $options["data-confirm"] = $options['confirm'];
            unset($options['confirm']);
        }

        return (new TagHelper)->tag('input', array_merge([ "type" => "submit", "name" => "commit", "value" => $value ], $options));
    }

    private function submit_default_value()
    {
        if (!$this->object)
            return 'Invia';

        if ($this->object->is_new_record())
            return 'Crea';
        return 'Modifica';
    }

    private static function html_options_for_form($url_for_options, array $options)
    {
        $options = new \Ay($options);
        if ($options->delete("multipart"))
            $options["enctype"] = "multipart/form-data";

        # The following URL is unescaped, this is just a hash of options, and it is the
        # responsibility of the caller to escape all the values.
        $options["action"]  = UrlHelper::url_for($url_for_options);
        $options["accept-charset"] = "UTF-8";

        if ($options->delete("remote"))
            $options["data-remote"] = true;

        if ($options["data-remote"] && empty($options["authenticity_token"]))
        {
          // The authenticity token is taken from the meta tag in this case
          $options["authenticity_token"] = false;
        }
        else if ($options["authenticity_token"] == true)
        {
            // Include the default authenticity_token, which is only generated when its set to nil,
            // but we needed the true value to override the default of no authenticity_token on data-remote.
            $options["authenticity_token"] = null;
        }

        return $options;
    }

    private static function utf8_enforcer_tag()
    {
        return TagHelper::tag('input', [ 'type' => "hidden", 'name' => "utf8", 'value' => "&#x2713;" ], false, false);
    }

    private static function extra_tags_for_form($html_options)
    {
        $authenticity_token = $html_options->delete("authenticity_token");
        $method = $html_options->delete("method");

        if (!strcasecmp($method, 'get'))
        {
            $html_options["method"] = "get";
            $method_tag = '';
        }
        else if (!strcasecmp($method, 'post'))
        {
            $html_options["method"] = "post";
            $method_tag = UrlHelper::token_tag($authenticity_token);
        }
        else
        {
            // PUT & DELETE through POST
            $html_options["method"] = "post";
            $method_tag = UrlHelper::method_tag($method);
            $method_tag .= UrlHelper::token_tag($authenticity_token);
        }

        $tags = self::utf8_enforcer_tag() . $method_tag;

        return TagHelper::content_tag('div', $tags, [ 'style' => 'margin:0;padding:0;display:inline' ], false);
    }
}
