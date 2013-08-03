<?php

namespace ActiveModel\Validations;

class PresenceValidator
{
    function validate_each($record, $attribute, $value)
    {
        if (empty($value))
            $record->errors()->add($attribute, ' non può essere vuoto');
    }
}