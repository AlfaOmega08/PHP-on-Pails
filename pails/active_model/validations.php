<?php

namespace ActiveModel;

trait Validations
{
    private $validations = [];

    function __trait_init_validations()
    {

    }

    private $_errors;
    function errors()
    {
        if ($this->_errors == null)
            $this->_errors = new \ActiveModel\Errors(get_class($this));
        return $this->_errors;
    }

    function is_valid($context = null)
    {
        try
        {
            foreach ($this->validations as $val)
            {
                $class = $val[0];
                $values = $val[1];

                foreach ($values as $value)
                    $class->validate_each($this, $value, $this->$val);
            }
        }
        catch (Exception $e)
        {
            //self::$validation_context = $current_context;
            throw $e;
        }

        return $this->errors()->is_empty();
    }

    function is_invalid($context = null)
    {
         return !$this->is_valid($context);
    }

    protected function run_validations()
    {
        $this->run_callbacks('before_validate');
        return $this->errors().is_empty();
    }

    function validates_confirmation_of()
    {
        $this->validations[] = [ new Validations\ConfirmationValidator(), func_get_args() ];
    }

    function validates_presence_of()
    {
        $this->validations[] = [ new Validations\PresenceValidator(), func_get_args() ];
    }

    function validates_format_of()
    {
        $args = func_get_args();
        $options = array_pop($args);

        $this->validations[] = [ new Validations\FormatValidator($options), $args ];
    }
}