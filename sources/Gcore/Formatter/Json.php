<?php
namespace Gcore\Formatter;

use Gcore\FormatterAbstract;

class Json extends FormatterAbstract
{
    protected $_callback;

    public function setCallback($callback)
    {
        if(!preg_match('/([a-zA-Z]{1})([a-zA-Z0-9\_]{0,127})$/Usi', $callback)) {
            throw new Exception('callback MUST contain a-zA-Z0-9_ characters and must start from character');
        }

        $this->_callback = $callback;
    }

    public function format()
    {
        $encoded = json_encode($this->_data);
        $this->_contentType = 'application/json';

        if (json_last_error()) {
            //todo something
        }

        if ($this->_callback) {
            $this->_contentType = 'text/javascript';
            return $this->_callback . '(' . $encoded . ');';
        }

        return $encoded;
    }
}
