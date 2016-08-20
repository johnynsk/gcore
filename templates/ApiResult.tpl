<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
    <meta name="robots" content="nofollow" />
    <meta name="robots" content="noindex" />
    <meta name="author" content="http://johnynsk.ru" />
    <title>{$title}</title>
    <link rel="icon" type="image/vdn.microsoft.icon" href="{$httproot}core/public/api.ico" />
    {$css}
    {$script}
    <script type="text/javascript" src="{$httproot}core/public/api.js"></script>
    <!--
        coding&design by johny
        http://johnynsk.ru/
        me@johnynsk.ru
    -->
</head>
<body$extstyle>
<div id="top"><h1>{$h1} <span class="right-floated"><a href="{$buttonlink}" class="ref-button">{$button}</a></span></h1></div>
<div id="wrap">
    <h2>{$title}{$links}</h2>
    {$subtitle}
    {$text}
    <p class="info">Вы также можете получить результат и в других форматах.<br />
        Доступные форматы: JSON, XML, Plain-Text, HTML, SOAP, PHP (serialize)</p>
</div>
<div id="copy"><span class="right-floated"><a href="mailto:{$mail}">{$mail}</a></span>
    <address>Generated with {$name}/{$version}, API Version: {$api_version} at {$date}</address></div>
</body>
</html>