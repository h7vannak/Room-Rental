<?php
$langCode = $_SESSION['user']['lang'] ?? 'en';
$langFile = __DIR__ . "/../lang/$langCode.php";

$lang = file_exists($langFile)
    ? require $langFile
    : require __DIR__ . "/../lang/en.php";

function __($key)
{
    global $lang;
    return $lang[$key] ?? $key;
}
