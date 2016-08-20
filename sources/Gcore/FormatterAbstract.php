<?php

namespace Gcore;

abstract class FormatterAbstract
{
    protected $_data;

    protected $_contentType;

    public function __construct($data)
    {
        $this->_data = $data;
    }

    public function getContentType()
    {
        return $this->_contentType;
    }
}
