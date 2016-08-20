<?php

namespace Gcore\Formatter;

use Gcore\FormatterAbstract;

class Soap extends FormatterAbstract
{
    /**
     * @var bool
     */
    protected $_isFault;

    /**
     * @var array
     */
    protected $_params = [];

    /**
     * Xml constructor.
     *
     * @param mixed $data
     */
    public function __construct($data)
    {
        $this->_contentType = 'text/xml';

        parent::__construct($data);
    }


    /**
     * @param $isFault
     *
     * @return $this
     */
    public function setIsFault($isFault)
    {
        $this->_isFault = $isFault;

        return $this;
    }

    /**
     * @param array $params
     *
     * @return $this
     */
    public function setParams(array $params)
    {
        $this->_params = $params;

        return $this;
    }

    /**
     * @return string
     */
    public function format()
    {
        if (!$this->_isFault) {
            $soapBody = <<<xml
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:s="http://www.w3.org/2001/XMLSchema"><SOAP-ENV:Body><soapResponse>%data;</soapResponse></SOAP-ENV:Body></SOAP-ENV:Envelope>
xml;
        } else {
            $soapBody = <<<xml
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:s="http://www.w3.org/2001/XMLSchema"><SOAP-ENV:Body><SOAP-ENV:Fault><faultcode>SOAP-ENV:Server</faultcode><faultstring>%message;</faultstring><detail>%data;</detail></SOAP-ENV:Fault></SOAP-ENV:Body></SOAP-ENV:Envelope>
xml;
        }

        $xml = (new Xml($this->_data, 'soapResult'))->$this->format();

        $xml = str_replace('<?xml version="1.0" encoding="UTF-8"?' . '>', '', $xml);
        $xml = str_replace('<?xml version="1.0"?' . '>', '', $xml);
        $result = str_replace("%data;", $xml, $soapBody);

        if (isset($this->_params["subtitle"])) {
            $result = str_replace("%message;", $this->_params["subtitle"], $result);
        }

        return $result;
    }
}
