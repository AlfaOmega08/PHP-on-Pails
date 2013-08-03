<?php

namespace ActionDispatch\Http\Mime;

class AcceptItem
{
    public function __construct($order, $name, $q = null)
    {
        $this->order = $order;
        $this->name = trim($name);

        if ($this->name == '*/*' && $q == null)
            $q = 0.0;

        if ($q == null)
            $q = 1.0;

        $this->q = (int) ($q * 100);
    }

    public function __toString()
    {
        return $this->name;
    }
}

class Type
{
    private static $registered = [];
    private static $lookup = [];
    private static $extension_lookup = [];

    public function __construct($fullname, $name = "", $synonyms = [])
    {
        $this->fullname = $fullname;
        $this->name = $name;
        $this->synonyms = $synonyms;
    }

    public static function lookup($type)
    {
        if (isset(self::$lookup[$type]))
            return self::$lookup[$type];
        return null;
    }

    public static function extension_lookup($type)
    {
        if (isset(self::$extension_lookup[$type]))
            return self::$extension_lookup[$type];
        return null;
    }

    public static function register($fullname, $name, $synonyms = [], $extensions = [])
    {
        $type = new Type($fullname, $name, $synonyms);

        self::$registered[] = $type;

        foreach (array_merge([ $fullname ], $synonyms) as $lookup)
            self::$lookup[$lookup] = $type;

        foreach (array_merge([ $name ], $extensions) as $lookup)
            self::$extension_lookup[$lookup] = $type;
    }

    const TRAILING_STAR_REGEXP = '/(text|app)\/\*/';
    const Q_SEPARATOR_REGEXP = '/;\s*q=/';

    public static function parse($accept_header)
    {
        if (!preg_match('/,/', $accept_header))
        {
            $accept_header = preg_split(self::Q_SEPARATOR_REGEXP, $accept_header)[0];
            if (preg_match(self::TRAILING_STAR_REGEXP, $accept_header, $matches))
                return self::parse_data_with_trailing_star($matches[1]);
            else
                return [ self::lookup($accept_header) ];
        }
        else
        {
            // keep track of creation order to keep the subsequent sort stable
            $list = [];
            $index = 0;

            foreach (explode(',', $accept_header) as $header)
            {
                $pieces = preg_split(self::Q_SEPARATOR_REGEXP, $header);
                $params = $pieces[0];
                $q = isset($pieces[1]) ? $pieces[1] : null;

                $params = trim($params);
                if (!empty($params))
                {
                    if (preg_match(self::TRAILING_STAR_REGEXP, $params, $matches))
                    {
                        $all_star = self::parse_data_with_trailing_star($matches[1]);
                        foreach ($all_star as $m)
                        {
                            $list[] = new AcceptItem($index, $m, $q);
                            $index += 1;
                        }
                    }
                    else
                    {
                        $list[] = new AcceptItem($index, $params, $q);
                        $index += 1;
                    }
                }
            }

            usort($list, function($a, $b)
            {
                if ($a->name == $b->name)
                    return 0;

                $res = -($a->q - $b->q);
                if ($res != 0)
                    return $res;

                return $a->order - $b->order;
            });

            // Take care of the broken text/xml entry by renaming or deleting it
            $text_xml = self::findByName($list, "text/xml");
            $app_xml = self::findByName($list, "app/xml");

            if ($text_xml && $app_xml)
            {
                # set the q value to the max of the two
                $list[$app_xml]->q = max($list[$text_xml]->q, $list[$app_xml]->q);

                # make sure app_xml is ahead of text_xml in the list
                if ($app_xml > $text_xml)
                {
                    list($list[$app_xml], $list[$text_xml]) = array($list[$text_xml], $list[$app_xml]);
                    list($app_xml, $text_xml) = array($text_xml, $app_xml);
                }

                # delete text_xml from the list
                unset($list[$text_xml]);
                $list = array_values($list);
            }
            else if ($text_xml)
                $list[$text_xml]->name = "app/xml";

            # Look for more specific XML-based types and sort them ahead of app/xml
            if ($app_xml)
            {
                $app_xml_type = $list[$app_xml];

                for ($i = $app_xml; $i < count($list); $i++)
                {
                    $type = $list[$i];
                    if ($type->q < $app_xml_type->q)
                        break;

                    if (preg_match('/\+xml$/', $type->name))
                    {
                        list($list[$app_xml], $list[$i]) = array($list[$i], $list[$app_xml]);
                        $app_xml = $i;
                    }
                }
            }

            $list = array_map(function($i)
            {
                return self::lookup($i->name);
            }, $list);

            return array_unique($list);
        }
    }

    public static function parse_data_with_trailing_star($name)
    {
        $ret = [];

        foreach (self::$lookup as $key => $val)
        {
            if (preg_match("/${name}/", $key))
                $ret[] = $key;
        }

        return $ret;
    }

    public function __toString()
    {
        return $this->fullname;
    }

    private static function findByName($list, $name)
    {
        for ($i = 0; $i < count($list); $i++)
        {
            if ($list[$i]->name == $name)
                return $i;
        }

        return null;
    }
}

Type::register("text/html", 'html', [ 'application/xhtml+xml' ], [ 'xhtml' ]);
Type::register("text/plain", 'text', [], [ 'txt' ]);
Type::register("text/javascript", 'js', [ 'application/javascript', 'application/x-javascript' ]);
Type::register("text/css", 'css');
Type::register("text/calendar", 'ics');
Type::register("text/csv", 'csv');

Type::register("image/png", 'png', [], [ 'png' ]);
Type::register("image/jpeg", 'jpeg', [], [ 'jpg', 'jpeg', 'jpe' ]);
Type::register("image/gif", 'gif', [], [ 'gif' ]);
Type::register("image/bmp", 'bmp', [], [ 'bmp' ]);
Type::register("image/tiff", 'tiff', [], [ 'tif', 'tiff' ]);

Type::register("video/mpeg", 'mpeg', [], [ 'mpg', 'mpeg', 'mpe' ]);

Type::register("application/xml", 'xml', [ 'text/xml', 'app/x-xml' ]);
Type::register("application/rss+xml", 'rss');
Type::register("application/atom+xml", 'atom');
Type::register("application/x-yaml", 'yaml', [ 'text/yaml' ]);

Type::register("multipart/form-data", 'multipart_form');
Type::register("application/x-www-form-urlencoded", 'url_encoded_form');

# http://www.ietf.org/rfc/rfc4627.txt
# http://www.json.org/JSONRequest.html
Type::register("application/json", 'json', [ 'text/x-json', 'application/jsonrequest' ]);

Type::register("application/pdf", 'pdf', [], [ 'pdf' ]);
Type::register("application/zip", 'zip', [], [ 'zip' ]);

# Create Mime::ALL but do not add it to the SET.
Type::register("*/*", 'all', []);