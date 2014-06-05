<?php

class Route
{
    public function __construct($url, $to, $options = [])
    {
        $this->url = $url;

        $to = trim($to);
        if (!empty($to))
            list($this->controller, $this->action) = explode('#', $to);

        foreach ($options as $option => $value)
            $this->$option = $value;

        if (!isset($this->via))
            $this->via = 'get';

        if (!isset($this->default) || !isset($this->default['format']))
            $this->default = ['format' => null ];
    }

    public function accepts($method)
    {
        if (is_string($this->via) && $this->via == $method)
            return true;
        else if (is_array($this->via))
        {
            foreach ($this->via as $v)
            {
                if ($v == $method)
                    return true;
            }
            return false;
        }
        else
            return false;
    }

    public function matches($url)
    {
        $parameter_names = $this->get_parameters();
        if ($this->format !== false)
            $parameter_names[] = 'format';

        if (preg_match($this->build_pattern(), $url, $matches))
        {
            array_shift($matches);

            $controller = $this->controller;
            $action = $this->action;

            $this->override_controller_action($parameter_names, $matches, $controller, $action);

            for ($i = 0; $i < count($matches); $i++)
                $parameters[$parameter_names[$i]] = trim($matches[$i], '/');

            if (empty($parameters['format']))
                $parameters['format'] = $this->default['format'];
            else
                $parameters['format'] = ltrim($parameters['format'], '.');

            return [
                'params' => $parameters,
                'controller' => $this->module . $controller,
                'action' => $action,
            ];
        }

        return false;
    }

    public function params_count()
    {
        return substr_count($this->url, '/:') + substr_count($this->url, '/*');
    }

    public function __get($name)
    {
        if (!isset($this->$name))
            return null;

        return $this->$name;
    }

    protected function constraint($parameter, $star)
    {
        if (is_array($this->constraints !== null))
        {
            if (isset($this->constraints[$parameter]))
                return $this->constraints[$parameter];
        }

        if ($star)
            return '[A-Za-z0-9\-\/]+';
        else
            return '[A-Za-z0-9\-]+';
    }

    private function build_pattern()
    {
        preg_match_all('#/([:\*][A-Za-z][A-Za-z0-9_]*)#', $this->url, $matches);

        $pattern = str_replace('/', '/+', $this->url);
        foreach ($matches[1] as $parameter)
        {
            $p_name = substr($parameter, 2);
            $star = $parameter[1] == '*';
            $regex = $this->constraint($p_name, $star);

            $pattern = str_replace($parameter, "({$regex})", $pattern);
        }

        if ($this->format !== false)
            $pattern .= '(\.[a-z][a-z0-9]*)?';

        return "#^{$pattern}(?:\/+)?$#";
    }

    private function get_parameters()
    {
        preg_match_all('#/[:\*]([A-Za-z][A-Za-z0-9_]*)#', $this->url, $matches);
        return $matches[1];
    }

    private function override_controller_action(&$parameter_names, &$matches, &$controller, &$action)
    {
        foreach ([ 'controller', 'action' ] as $override)
        {
            if (($pos = array_search($override, $parameter_names)) !== false)
            {
                if (isset($matches[$pos]))
                {
                    $$override = trim($matches[$pos], "/");

                    unset($parameter_names[$pos], $matches[$pos]);
                    $parameter_names = array_values($parameter_names);
                    $matches = array_values($matches);
                }
            }
        }
    }
}
