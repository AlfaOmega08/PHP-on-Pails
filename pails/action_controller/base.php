<?php

namespace ActionController;

class Base
{
    use RequestForgeryProtection;

    private $layout = "layouts/application";
    private $view;

    public function __construct()
    {
        self::define_callback('filter');

        $this->params = \Request::parameters();
        $this->format = \Request::get_format()->name;
    }

    public function action($name)
    {
        if (!method_exists($this, $name))
            throw new \Exception("No route matches " . $this->controller_name() . "::{$name}.");

        $this->action = $name;

        self::fire_callbacks('before_filter');

        $this->view = $this->controller_name() . "/$name";
        $this->in_action = true;
        call_user_func(array($this, $name));
        $this->in_action = false;

        if (!isset($this->response_body))
            $this->do_render();
        else
            echo $this->response_body;

        self::fire_callbacks('after_filter');
    }

    public function controller_name()
    {
        $name = explode('_', underscore(get_class($this)));
        return implode('_', array_splice($name, 0, -1));
    }

    public function render($view, $options = [])
    {
        if ($this->in_action)
        {
            if (strstr($view, "/") !== FALSE)
                $this->view = $view;
            else
                $this->view = $this->controller_name() . "/$view";
            return "";
        }

        if ($view !== "")
        {
            if (strstr($view, '/') !== FALSE)
                return $this->render_file($view . "." . $this->format . ".php");
            return $this->render_file($this->controller_name() . '/' . $view . "." . $this->format . ".php");
        }

        if (isset($options['partial']))
        {
            if (strstr($options['partial'], '/') !== FALSE)
            {
                $path = explode('/', $options['partial']);
                $path[count($path) - 1] = '_' . $path[count($path) - 1];
                $view = implode('/', $path);
                return $this->render_file($view . "." . $this->format . ".php");
            }

            return $this->render_file($this->controller_name() . '/_' . $options['partial'] . "." . $this->format . ".php");
        }

        throw new \Exception("What to render?");
    }

    private function do_render()
    {
        if ($this->format == 'html')
        {
            $view = $this->render_file($this->view . ".html.php");
            $output = $this->render_file($this->layout . ".html.php", [ 'content' => $view ]);
            echo $output;
        }
    }

    private function render_file($view_path, $variables = [])
    {
        ob_start();

        include_once(ROOT . '/pails/ViewUtils.php');

        foreach (glob(ROOT . "/pails/action_view/helpers/*.php") as $filename)
        {
            include_once($filename);
        }

        extract($variables);
        extract(get_object_vars($this));
        $controller = $this;
        include(ROOT . '/app/views/' . $view_path);

        $data = ob_get_contents();
        ob_end_clean();

        return $data;//$this->cleanup_output($data);
    }

    private function cleanup_output($data)
    {
        $search = [
            '/\>[^\S ]+/s', //strip whitespaces after tags, except space
            '/[^\S ]+\</s', //strip whitespaces before tags, except space
            '/(\s)+/s'  // shorten multiple whitespace sequences
        ];
        $replace = [ '>', '<', '\\1' ];

        return preg_replace($search, $replace, $data);
    }

    public function redirect_to($path, $options = [])
    {
        if (isset($this->response_body))
            throw new Exception("Double redirect/render!");

        $status = isset($options['status']) ? $options['status'] : 302;

        \header(' ', true, $status);
        \header('Location: ' . $path);

        $this->response_body = "<!DOCTYPE html><html><head><title>{$status} Redirect</title></head><body>You are being <a href=\"{$path}\">redirected</a>.</body></html>";
    }

    public static function cookie($name, $value, $options = [])
    {
        $expire = null;

        if (isset($options['expires']))
        {
            if (is_int($options['expires']))
                $expire = $options['expires'];
            else if (is_a($options['expires'], 'DateTime'))
                $expire = $options['expires']->getTimestamp();
            else
                throw new Exception('expires options must be a DateTime object or a timestamp');
        }

        if (isset($options['permanent']) && $options['permanent'] == true)
        {
            // 20 Years
            $expire = time() + 631138519;
        }

        if (isset($options['signed']) && $options['signed'] == true)
        {
            $secret_key = 'DEADBEEF';
            $value = $value . "_____" . sha1($value . $secret_key);
        }

        setcookie($name, $value, $expire, '/', null, null, true);
    }

    public static function get_cookie($name, $options = [])
    {
        if (!isset($_COOKIE[$name]))
            return null;

        $val = $_COOKIE[$name];
        if (isset($options['signed']) && $options['signed']);
        {
            $secret_key = 'DEADBEEF';

            $val = explode('_____', $val);
            if (count($val) != 2)
                throw new \Exception("HACK Alert: TAMPERED COOKIE!");

            if (sha1($val[0] . $secret_key) != $val[1])
                throw new \Exception("HACK Alert: TAMPERED COOKIE!");

            $val = $val[0];
        }

        return $val;
    }

    public static function delete_cookie($name)
    {
        setcookie($name, 'deleted', time() - 86400 * 10);
    }

    public static function prepend_before_action($name)
    {
        array_unshift(self::$accepted_callbacks['before_filter'], [ $name, [] ]);
    }

    private static $accepted_callbacks = [];

    static function define_callback($name)
    {
        self::$accepted_callbacks['before_' . $name] = [];
        self::$accepted_callbacks['after_' . $name] = [];
    }

    static function register_callback($name, $method, $options = [])
    {
        if (isset(self::$accepted_callbacks[$name]))
            self::$accepted_callbacks[$name][] = [ $method, $options ];
    }

    function fire_callbacks($name)
    {
        if (isset(self::$accepted_callbacks[$name]))
        {
            foreach (self::$accepted_callbacks[$name] as $callback)
            {
                $cb = $callback[0];
                $opt = $callback[1];

                if (isset($opt['only']))
                {
                    if (in_array($this->action, $opt['only']))
                        call_user_func([$this, $cb]);
                }
                else if (isset($opt['except']))
                {
                    if (!in_array($this->action, $opt['except']))
                        call_user_func([$this, $cb]);
                }
                else
                    call_user_func([$this, $cb]);
            }
        }
    }
}
