<?php

namespace Gcore\Formatter;

use Gcore\FormatterAbstract;

class Xml extends FormatterAbstract
{
    /**
     * @var string
     */
    protected $_rootName;

    /**
     * Xml constructor.
     *
     * @param mixed $data
     * @param string $rootName
     */
    public function __construct($data, $rootName = 'data')
    {
        $this->_contentType = 'text/xml';
        $this->_rootName = $rootName;
        parent::__construct($data);
    }

    /**
     * @return string
     */
    public function format()
    {
        $parent = new \SimpleXMLElement("<{$this->_rootName}></{$this->_rootName}>");

        $this->_prepareNode($this->_data, $parent);
        $result = $parent->asXML();

        $result = str_replace("&lt;![CDATA[", "<![CDATA[", $result);
        $result = str_replace("]]&gt;", "]]>", $result);

        return $result;
    }

    /**
     * Устанавливает корневое имя
     *
     * @param string $rootName
     */
    public function setRootName($rootName)
    {
        $this->_rootName = $rootName;
    }

    /**
     * Добавляет элементу аттрибуты
     *
     * @param $data
     * @param \SimpleXMLElement $xml
     *
     * @return \SimpleXMLElement
     */
    private function _prepareAttribute($data, \SimpleXMLElement $xml)
    {
        foreach ($data as $key => $value) {
            if ($key != GENERIC_CORE_CDATA) {
                $xml->addAttribute($key, $value);
            }
        }

        return $xml;
    }


    /**
     * Готовит ноду
     *
     * @param $data
     * @param \SimpleXMLElement $xml
     */
    private function _prepareNode($data, \SimpleXMLElement $xml)
    {
        foreach ($data as $key => $value) {
            $order = false;

            if (is_numeric($key)) {
                $order = (string)$key;
                $key = "item";
            }

            if ($key == GENERIC_CORE_ATTR) {
                $this->_prepareAttribute($value, $xml);
                continue;
            }

            if (is_string($value) || is_numeric($value) || is_bool($value)) {
                if (is_bool($value)) {
                    if ($value) {
                        $value = "true";
                    } else {
                        $value = "false";
                    }
                }
                if (is_numeric($value) || is_bool($value)) {
                    $item = $xml->addChild($key, (string)$value);
                } else if (!preg_match('/^([0-9a-zA-Z-_ а-я\/\"\'\:\,\.\(\)\;]*)$/usi', $value)) {
                    $item = $xml->addChild($key, "<![CDATA[$value]]>");
                } else {
                    $item = $xml->addChild($key, $value);
                }
                if (null != $order) {
                    if ($order == 0) {
                        $item->addAttribute("order", "0");
                    } else {
                        $item->addAttribute("order", $order);
                    }
                }
                continue;
            }
            if (isset($value) && isset($value->{GENERIC_CORE_ATTR})
                && isset($value->{GENERIC_CORE_ATTR}->{GENERIC_CORE_CDATA})
            ) {
                $item = $xml->addChild($key, "<![CDATA[" . $value->{GENERIC_CORE_ATTR}->{GENERIC_CORE_CDATA} . "]]>");
            } else {
                $item = $xml->addChild($key, null);
            }

            if (null != $order) {
                if ($order == 0) {
                    $item->addAttribute("order", "0");
                } else {
                    $item->addAttribute("order", $order);
                }
            }
            $this->_prepareNode($value, $item);
        }
    }
}
