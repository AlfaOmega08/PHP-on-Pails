<?php

namespace ActiveModel\Validations;

class FormatValidator
{
    private $options;

    function __construct(array $options)
    {
        if (count($options) != 1)
            throw new Exception("Bad format");

        if (!isset($options['with']) && !isset($options['without']))
            throw new Exception("Bad format");

        $this->options = $options;
    }

    function validate_each($record, $attribute, $value)
    {
        if (isset($this->options['with']))
        {
            if (!preg_match($this->options['with'], $value))
                $record->errors()->add($attribute, 'Formato non corretto');
        }
        else
        {
            if (preg_match($this->options['without'], $value))
                $record->errors()->add($attribute, 'Formato non corretto');

        }
    }
}