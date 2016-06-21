<?php namespace Ddsm\Szrk;


class Curl
{
    /**
     * PHP-CURL http get请求
     * @param string $url       url地址
     * @param array  $params    参数
     * @return string
     */
    public static function httpGet($url, $params = array())
    {
        if (!function_exists('curl_init')) exit('PHP CURL扩展未开启');
        $ci = curl_init();
        if (!empty($params)) {
            $url = $url . (strpos($url, '?') ? '&' : '?')
                . (is_array($params) ? http_build_query($params) : $params);
        }
        curl_setopt($ci, CURLOPT_URL, $url);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($ci);
        curl_close($ci);
        return $response;
    }

    /**
     * PHP-CURL http post请求
     * @param string $url       url地址
     * @param array  $data      数据
     * @param array  $params    参数
     * @return string
     */
    public static function httpPost($url, $data, $params = array())
    {
        if (!function_exists('curl_init')) exit('PHP CURL扩展未开启');
        $curl = curl_init();
        if (!empty($params)) {
            $url = $url . (strpos($url, '?') ? '&' : '?')
                . (is_array($params) ? http_build_query($params) : $params);
        }
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }
}