<?php

header('Content-Type:text/html;charset=utf-8');

$url = "https://www.ixigua.com/home/3900699669442371/video/";

echo $cookie = getCookie($url);

var_dump(myCurl($url, $cookie));

function myCurl($url, $cookie, $fromUrl = 'https://www.ixigua.com/', $flag = 'get', $paramStr = '')
{
    $user_agent = "Mozilla/5.66 (Windows NT 6.1; WOW64; rv:33.0) Gecko/20100101 Firefox/33.0";

    $curl = curl_init();
    if ($flag == 'post') {
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $paramStr);
    }
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_REFERER, $fromUrl);
    curl_setopt($curl , CURLOPT_COOKIE , $cookie);
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);
    curl_setopt($curl, CURLOPT_USERAGENT, $user_agent);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $str = curl_exec($curl);
    curl_close($curl);
    return $str;
}

function getCookie($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $content = curl_exec($ch);
    curl_close($ch);

    list($header, $body) = explode("\r\n\r\n", $content);
    preg_match_all("/set-cookie:(.*?);/i", $header, $matches);
    if ($matches && !empty($matches[1]))
        return implode(';', $matches[1]);

    return '';
}

/**
 * 获取正则匹配信息
 *
 * @param $pattern
 * @param $subject
 * @return string
 */
function getPregMatch($pattern, $subject)
{
    preg_match($pattern, $subject, $matches);
    if ($matches && !empty($matches[1]))
        return $matches[1];

    return '';
}

function dd($data)
{
    echo '<pre>';
    print_r($data);
    echo '</pre>';
    exit();
}
