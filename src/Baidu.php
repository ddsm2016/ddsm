<?php namespace Ddsm;


class Baidu
{
    /**
     * 身份证验证
     * @param string $idCard 身份证号码
     * @return bool|array 当身份证格式错误时返回False,否则返回身份证信息(包括出生地、性别和生日)
     * @throws \Exception 当一些意外发生时，意外情况请参考api接口文档
     */
    public static function idCard($idCard)
    {
        $header = [
            'apikey:  b654ab0501bec3f6ccdd3701d92a5b9d',
        ];
        $url = 'http://apis.baidu.com/apistore/idservice/id?id='.$idCard;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER  , $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch , CURLOPT_URL , $url);
        $res = curl_exec($ch);
        $res = json_decode($res, true);
        switch ($res['errNum']) {
            case 0:
                return $res['retData'];
            case -1:
                return false;
            default:
                throw new \Exception($res['retMsg'], $res['errNum']);
        }
    }

    /**
     * 手机号码验证
     * @param string $mobile 手机号码
     * @return array|bool
     */
    public static function mobile($mobile)
    {
        $ch = curl_init();
        $url = 'http://apis.baidu.com/apistore/mobilenumber/mobilenumber?phone='.$mobile;
        $header = array(
            'apikey: b654ab0501bec3f6ccdd3701d92a5b9d',
        );
        // 添加apikey到header
        curl_setopt($ch, CURLOPT_HTTPHEADER  , $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // 执行HTTP请求
        curl_setopt($ch , CURLOPT_URL , $url);
        $res = curl_exec($ch);
        $res = json_decode($res, true);
        if ($res['errNum']===0) {return $res['retData'];
        } else {
            return false;
        }
    }
}