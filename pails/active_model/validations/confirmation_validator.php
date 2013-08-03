<?php

namespace ActiveModel\Validations;

class ConfirmationValidator
{
    function validate_each($record, $attribute, $value)
    {
        $confirmed = $attribute . '_confirmation';
        $confirmed = $record->$confirmed;

        if ($confirmed != $value)
            $record->errors()->add($attribute . '_confirmation', ' non confermato');
    }
}