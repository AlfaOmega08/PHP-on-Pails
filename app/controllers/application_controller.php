<?php

class ApplicationController extends ActionController\Base
{
    function __construct()
    {
        parent::__construct();

        $this->protect_from_forgery();
    }
}
