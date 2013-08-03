<?php

class String
{
    private $string;

    public function __construct($string = "")
    {
        $this->string = $string;
    }

    public function __toString()
    {
        return $this->string;
    }

    public function split($match = ' ', $limit = null)
    {
        if ($match == ' ')
        {
            $str = ltrim(preg_replace('/ +/', ' ', $this->string), ' ');
            $ret = explode($match, $str);
        }
        else
            $ret = explode($match, $this->string);

        if ($limit < 0)
            return $ret;

        switch ($limit)
        {
            case null:
                while (empty($ret[count($ret) - 1]))
                    unset($ret[count($ret) - 1]);

                return $ret;
            case 1:
                return [ $this->string ];
            default:
                if ($limit >= count($ret))
                    return $ret;

                $corrected = []
                for ($i = 0; $i < $limit - 1; $i++)
                    $corrected[$i] = $ret[$i];

                $corrected[$limit - 1] = implode($match, array_splice($ret, $limit - 1));
                return $corrected;
        }
    }

    public function rsplit($regex, $limit)
    {
        return preg_split($regex);
    }

    public function squeeze($chars = null)
    {
        if ($chars == null)
            return preg_replace('/(.)\\1+/', '$1', $this->string);

        throw new Exception("Sqeeze not implemented.");
    }

    public function unpack($format)
    {
        return unpack($format, $this->string);
    }

    public function upcase()
    {
        return new String(strtoupper($this->string));
    }

    public function _upcase()
    {
        $this->string = strtoupper($this->string);
        return $this;
    }
}
