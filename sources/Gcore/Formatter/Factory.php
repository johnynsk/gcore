<?php

namespace Gcore\Formatter;

class Factory
{
    public static function getXml($data)
    {
        return new Xml($data);
    }

    public static function getJson($data)
    {
        return new Json($data);
    }

    public static function getYaml($data)
    {
        return new Yaml($data);
    }

    public static function getSoap($data)
    {
        return new Soap($data);
    }
}