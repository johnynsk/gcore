<?php
namespace Gcore\Formatter;

use Gcore\FormatterAbstract;

class Yaml extends FormatterAbstract
{
    public function __construct($data)
    {
        parent::__construct($data);

        $this->_data = json_decode(json_encode($this->_data),true);
        $this->_contentType = 'application/x-yaml';
    }

    public function format()
    {
        return yaml_emit($this->_data);
    }
}
