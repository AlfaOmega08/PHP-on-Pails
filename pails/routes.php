<?php

class Routes
{
    public static $routes = [];
    private $scope;
    private $helper_scope;
    private $implicit_options = [ 'module' => '' ];

    public static function draw($callback)
    {
        $callback(new Routes());
    }

    public function root($route, array $options = [])
    {
        $options['as'] = 'root';
        $options['format'] = false;
        $this->match('/', $route, $options);
    }

    public function resources($name, $options = [], callable $child = null)
    {
        if (is_array($name))
        {
            foreach ($name as $res)
                $this->resources($res, $options, $child);

            return;
        }

        if (isset($options['module']))
            $options['module'] .= '\\';

        $this->match("{$this->scope}/{$name}", "{$name}#index", array_merge([ 'as' => $this->helper_scope . $name ], $this->implicit_options, $options));
        $this->match("{$this->scope}/{$name}", "{$name}#create", array_merge([ 'via' => 'post' ], $this->implicit_options, $options));
        $this->match("{$this->scope}/{$name}/build", "{$name}#build", array_merge([ 'as' => "build_{$this->helper_scope}" . singular($name) ], $this->implicit_options, $options));
        $this->match("{$this->scope}/{$name}/:id/edit", "{$name}#edit", array_merge([ 'as' => "edit_{$this->helper_scope}" . singular($name) ], $this->implicit_options, $options));
        $this->match("{$this->scope}/{$name}/:id", "{$name}#show", array_merge([ 'as' => "{$this->helper_scope}" . singular($name) ], $this->implicit_options, $options));
        $this->match("{$this->scope}/{$name}/:id", "{$name}#update", array_merge([ 'via' => 'put' ], $this->implicit_options, $options));
        $this->match("{$this->scope}/{$name}/:id", "{$name}#destroy", array_merge([ 'via' => 'delete' ], $this->implicit_options, $options));

        if ($child)
        {
            $old_scope = $this->scope;
            $old_helper_scope = $this->helper_scope;
            $this->scope .= "/{$name}/:" . singular($name) . "_id";
            $this->helper_scope .= singular($name) . "_";

            $child($this);

            $this->scope = $old_scope;
            $this->helper_scope = $old_helper_scope;
        }
    }

    public function resource($name, $options = [], callable $child = null)
    {
        if (is_array($name))
        {
            foreach ($name as $res)
                $this->resource($res, $options, $child);

            return;
        }

        if (isset($options['module']))
            $options['module'] .= '\\';

        $c_name = plural($name);

        $this->match("{$this->scope}/{$name}/new", "{$c_name}#build", array_merge([ 'as' => "build_{$this->helper_scope}" . singular($name) ], $this->implicit_options, $options));
        $this->match("{$this->scope}/{$name}", "{$c_name}#create", array_merge([ 'via' => 'post' ], $this->implicit_options, $options));
        $this->match("{$this->scope}/{$name}", "{$c_name}#show", array_merge([ 'as' => $this->helper_scope . $name ], $this->implicit_options, $options));
        $this->match("{$this->scope}/{$name}/edit", "{$c_name}#edit", array_merge([ 'as' => "edit_{$this->helper_scope}" . singular($name) ], $this->implicit_options, $options));
        $this->match("{$this->scope}/{$name}", "{$c_name}#update", array_merge([ 'via' => 'put' ], $this->implicit_options, $options));
        $this->match("{$this->scope}/{$name}", "{$c_name}#destroy", array_merge([ 'via' => 'delete' ], $this->implicit_options, $options));

        if ($child)
        {
            $old_scope = $this->scope;
            $old_helper_scope = $this->helper_scope;
            $this->scope .= "/{$name}/:id";
            $this->helper_scope .= singular($name) . "_";

            $child($this);

            $this->scope = $old_scope;
            $this->helper_scope = $old_helper_scope;
        }
    }

    public function _namespace($name, callable $child)
    {
        $old_module = $this->implicit_options['module'];
        $old_scope = $this->scope;
        $old_helper_scope = $this->helper_scope;
        $this->implicit_options['module'] .= "$name\\";
        $this->scope .= "/{$name}";
        $this->helper_scope .= singular($name) . "_";

        $child($this);

        $this->implicit_options['module'] = $old_module;
        $this->scope = $old_scope;
        $this->helper_scope = $old_helper_scope;
    }

    public function module($name, callable $child)
    {
        $old_module = $this->implicit_options['module'];
        $this->implicit_options['module'] .= "$name\\";

        $child($this);

        $this->implicit_options['module'] = $old_module;
    }

    public function scope($name, callable $child)
    {
        $old_scope = $this->scope;
        $this->scope .= "/{$name}";

        $child($this);

        $this->scope = $old_scope;
    }

    public function match($path, $route, array $options = [])
    {
        self::$routes[] = new Route($path, $route, $options);
    }

    public static function get_route($name)
    {
        $result = [];

        foreach (self::$routes as $route)
        {
            if ($route->as == $name)
                $result[] = $route;
        }

        return count($result) ? $result : null;
    }

    public static function resolve($path)
    {
        foreach (self::$routes as $route)
        {
            if (!$route->accepts(Request::request_method()))
                continue;

            if (($result = $route->matches($path)) !== false)
            {
                Request::set_path_parameters($result['params']);
                return $result;
            }
        }

        throw new Exception("No route matched '{$path}'");
    }

    public static function dump()
    {
        echo "<table><thead><tr><th>Name</th><th>Method</th><th>Url</th><th>Destination</th></tr></thead>";

        foreach (self::$routes as $route)
        {
            echo "<tr>";
                echo "<td>{$route->as}</td>";
                if (is_array($route->via))
                    echo "<td>" . implode(", ", $route->via) . "</td>";
                else
                    echo "<td>{$route->via}</td>";
                echo "<td>$route->url</td>";
                echo "<td>{$route->controller}#{$route->action}</td>";
            echo "</tr>";
        }

        echo "</table>";
    }
}
