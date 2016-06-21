<?php 
namespace Ddsm\Szrk;

use Ddsm\Curl;

/**
 * 神州软科短信发送类
 */
class Sms
{
    /**
     * 神州软科Api SDK地址
     */
    const SZRK_URL = 'http://api.bjszrk.com/sdk/';

    /**
     * @var string 账号
     */
    protected $user;
    /**
     * @var string 密码
     */
    protected $pwd;

    /**
     * @var array 配置参数
     */
    protected $config = [
        'sign'      => '忻州东大',       //短信签名
        'send_time' => '',       //定时发送
        'encode'    => 'utf-8',  // 短信内容字符集
    ];

    /**
     * 构造函数
     *
     * @param string $user 用户名
     * @param string $pwd  密码
     * @param array $config=[] 配置项
     */
    public function __construct($user, $pwd, array $config=[])
    {
        $this->user = (string) $user;
        $this->pwd  = (string) $pwd;
        if (is_array($config)) {
            $this->config = array_merge($this->config, $config);
        }
    }

    /**
     * 发送短信(单条)
     * @param string $mobile   手机号
     * @param string $content  发送内容
     * @return string 发送状态ID 数据字串格式
     * @throws \Exception 当发送失败时
     */
    public function sentOne($mobile, $content)
    {
        $res = intval(Curl::httpPost(self::SZRK_URL.'BatchSend.aspx', [
            'CorpID'    => $this->user,
            'Pwd'       => $this->pwd,
            'Mobile'    => $mobile,
            'Content'   => $content.'【'.$this->config['sign'].'】',
            'Cell'      => '',
            'SendTime'  => $this->config['send_time'],
            'encode'    => $this->config['encode'],
        ]));
        return $this->check($res) ? $res : false;
    }

    /**
     * 剩余短信条数提醒
     * 当剩余短信数等于设定值时，会自动发送一条短信至指定手机号
     * @param int    $warn   发送短信剩余条数提醒的设定值
     * @param string $mobile 接收短信提醒的手机号
     */
    public function warnSms($warn, $mobile)
    {
        if ($this->selSum() <= $warn) {
            $content = '账号['.$this->user.']剩余短信数已不足'.$warn.'条,请及时充值。';
            $this->sentOne($mobile, $content);
        }
    }

    /**
     * 获取短信剩余条数
     * @return int 剩余条数
     * @throws \Exception
     */
    public function selSum()
    {
        $res = intval(Curl::httpPost(self::SZRK_URL.'SelSum.aspx', [
            'CorpID'    => $this->user,
            'Pwd'       => $this->pwd
        ]));
        return $this->check($res) ? $res : false;
    }

    /**
     * 检查返回值错误
     * @param int $res 接口返回值
     * @return bool
     * @throws \Exception
     */
    protected function check($res)
    {
        /**
         * @var array 接口返回错误码清单
         */
        $errorCode = [
            -1    => '账号未注册',
            -2    => '其他错误',
            -3    => '帐号或密码错误',
            -4    => '一次提交信息不能超过10000个手机号码，号码逗号隔开',
            -5    => '余额不足，请先充值',
            -6    => '定时发送时间不是有效的时间格式',
            -8    => '发送内容需在3到250字之间',
            -9    => '发送号码为空',
            -104  => '短信内容包含关键字',
        ];
        if ($res < 0) {
            throw new \Exception($errorCode[$res], $res);
        }
        return true;
    }
}
