<?php namespace Ddsm\Ehd;

use SoapClient;

/**
 * 亿惠达会员接口客户端类库
 */
class Hy
{
    /**
     * @var array 亿惠达服务接口清单
     */
    protected $services = [
        'addhyda'       => ['pm_xml',       'addhydaResult'],
        'readhyda'      => ['pm_qryhydaxml','readhydaResult'],
        'readhyfljh'    => ['pm_fljhxml',   'readhyfljhResult'],
        'readhyfljhspkc'=> ['pm_qryspxml',  'readhyfljhspkcResult'],
        'readhyjf'      => ['pm_qryhyjfxml','readhyjfResult'],
        'chghyjf'       => ['pm_xml',       'chghyjfResult'],
        'chghyda'       => ['pm_xml',       'chghydaResult'],
        'dohyjfdh'      => ['pm_hyjfxml',   'dohyjfdhResult'],
    ];

    /**
     * 亿惠达接口SoapClient对象
     *
     * @var \SoapClient
     */
    protected $soapClient;

    public function init($wsdl)
    {
        $this->soapClient = new SoapClient($wsdl);

        return $this;
    }

    /**
     * 读取亿惠达会员档案
     *
     * @param array $param 会员档案查询数据
     *
     * @return array
     *
     * @throws \Exception 当亿惠达接口调用失败时
     */
    public function readHyda(array $param)
    {
        $result = $this->callService('readhyda', $this->parseReadHydaParam($param));

        return $this->filterResult($result);
    }

    /**
     * 读取亿惠达会员积分
     *
     * @param array $param
     *
     * @return array
     *
     * @throws \Exception 当该亿惠达接口调用失败时
     */
    public function readHyjf(Array $param)
    {
        $result = $this->callService('readhyjf', $this->parseReadHyjfParam($param));

        return $this->filterResult($result);
    }

    /**
     * 读取亿惠达返利计划
     *
     * @param array $param      返利计划查询参数
     *
     * @param array $itemList   返利计划礼品列表(引用返回)
     *
     * @return array 返利计划数据
     *
     * @throws \Exception 当读取返利计划失败时
     */
    Public function readFljh(array $param, array &$itemList=[])
    {
        $result = $this->callService('readhyfljh', $this->parseReadFljhParam($param));

        $resultCode     = (int) $result['resultCode'];
        $resultMessage  = $result['resultMessage'];

        $repose = [];

        switch ($resultCode) {
            case -1:
                e($resultMessage);
                break;
            case 0:
                break;
            case 1:
                $fljh = $resultMessage['item'];

                $repose[$fljh['fljhid']] = [
                    'fljhid'   => $fljh['fljhid'],
                    'jfflcomm' => $fljh['jfflcomm'],
                    'fltype'   => $fljh['fltype'],
                    'sdate'    => $fljh['sdate'],
                    'edate'    => $fljh['edate']
                ];

                $itemList[$fljh['fljhid']] = [[
                    'itemid'   => $fljh['itemid'],
                    'jh_qty'   => $fljh['jh_qty'],
                    'jfflkc'   => $fljh['jfflkc']
                ]];
                break;
            default:
                $fljh = $resultMessage['item'];
                $fljhIds = [];
                foreach ($fljh as $value) {
                    if (!in_array($value['fljhid'], $fljhIds)) {
                        $fljhIds[] = $value['fljhid'];
                        $repose[$value['fljhid']] = array_intersect_key($value,
                            ['fljhid'=>'','jfflcomm'=>'','fltype'=>'','sdate'=>'','edate'=>'']
                        );
                    }

                    $itemList[$value['fljhid']][] = array_intersect_key($value,
                        ['itemid'=>'','jh_qty'=>'','jfflkc'=>'']
                    );
                }
                break;
        }

        return $repose;
    }

    /**
     * 读取亿惠达商品库存
     *
     * @param array $param 库存查询条件
     * @param array $info 库存查询结果礼品相关信息(引用返回)
     *
     * @return array
     *
     * @throws \Exception 当查询出错时
     */
    public function readSpkc(array $param, array &$info=[])
    {
        $result = $this->callService('readhyfljhspkc', $this->parseReadSpkcParam($param));

        $resultCode     = (int) $result['resultCode'];
        $resultMessage  = $result['resultMessage'];

        $repose = [];

        switch ($resultCode) {
            case -1:
                e($resultMessage);
                break;
            case 0:
                break;
            case 1:
                $spkc = $resultMessage['item'];
                $repose[$spkc['itemid']] = (int) $spkc['spqty'];
                unset($spkc['spqty']);
                $info[$spkc['itemid']] = $spkc;
                break;
            default:
                $spkc = $resultMessage['item'];
                $repose = [];
                foreach ($spkc as $value) {
                    $repose[$value['itemid']] = (int) $value['spqty'];
                    unset($value['spqty']);
                    $info[$value['itemid']] = $value;
                }
                break;
        }

        return $repose;
    }

    /**
     * 新增亿惠达会员档案
     *
     * @param array $param
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function addHyda(Array $param)
    {
        $result = $this->callService('addhyda', $this->parseAddHydaParam($param));

        return (bool) $this->filterPostResult($result);
    }

    /**
     * 修改亿惠达会员档案
     *
     * @param array $param 新会员档案数据
     *
     * @return bool
     */
    public function saveHyda(array $param)
    {
        $paramOld = $this->readHyda(['custid'=>$param['customerid']])[0];
        foreach ($paramOld as $k => $v) {
            if (empty($v)) {
                $paramOld[$k] = '';
            }
        }
        $param = array_merge($paramOld, $param);
        $result = $this->callService('chghyda', $this->parseSaveHydaParam($param));

        return (bool) $this->filterPostResult($result);
    }

    /**
     * 增加/调整会员积分
     *
     * @param string $customerid    会员卡号
     * @param int    $newjf         调整积分数
     * @param string $comm          备注
     *
     * @return bool
     */
    public function addHyjf($customerid, $newjf, $comm='')
    {
        $hyJf = $this->readHyjf(['custid'=>strval($customerid)]); //dump($hyJf);
        $oldjf = ($hyJf[0]['xsjf']*100 - $hyJf[0]['fljf']*100)/100; // float精度问题
        $xml = $this->parseRequestXml($this->parseArrToXml([
            'customerid' => strval($customerid),
            'oldjf' => $oldjf,
            'newjf' => intval($newjf),
            'comm'  => strval($comm),
        ]));

        $result = $this->callService('chghyjf', $xml);

        return (bool) $this->filterPostResult($result);
    }

    /**
     * 提交积分返利
     *
     * @param array $param 积分返利数据
     *
     * @return bool
     *
     * @throws \Exception 当提交失败时
     */
    public function addJfdh(array $param)
    {
        $result = $this->callService('dohyjfdh', $this->parseAddJfdhParam($param));

        return (bool) $this->filterPostResult($result);
    }

    /**
     * 调用亿惠达接口服务
     *
     * @param string $serviceName   服务名称
     * @param string $param         XML服务请求参数
     *
     * @return array
     */
    protected function callService($serviceName, $param)
    {
        $SERVICES = $this->services;

        $ehdData = $this->soapClient->$serviceName([
            $SERVICES[$serviceName][0] => $param
        ]);

        $ehdDataXml = get_object_vars($ehdData)[$SERVICES[$serviceName][1]];

        return json_decode(json_encode(
            simplexml_load_string($ehdDataXml, 'SimpleXMLElement', LIBXML_NOCDATA)
        ), true) ?: [];
    }

    /**
     * 合并两个数组,并且仅返回合并后数组与第一个数组参数的交集
     *
     * @param array $default    数组
     * @param array $param      参照数组
     *
     * @return array
     */
    protected function handlerParam(array $default, array $param)
    {
        return array_intersect_key(array_merge($default, $param), $default);
    }

    /**
     * 读取会员档案参数解析
     *
     * @param array $param
     *
     * @return string
     */
    protected function parseReadHydaParam($param)
    {
        $param = $this->handlerParam($this->arrayToKey([
            'custid', 'custname', 'custsjh', 'custsfz', 'ynallnew',
        ]), $param);

        return $this->xmlEncode($param, 'request');
    }

    /**
     * 读取会员积分参数解析
     *
     * @param array $param
     *
     * @return string
     */
    protected function parseReadHyjfParam($param)
    {
        $param = $this->handlerParam($this->arrayToKey(['custid', 'custname', 'ynallnew',]), $param);

        return $this->xmlEncode($param, 'request');
    }

    /**
     * 读取返利计划参数解析
     *
     * @param array $param
     *
     * @return string
     */
    protected function parseReadFljhParam($param)
    {
        $param = $this->handlerParam($this->arrayToKey(['fljhid', 'bdate',]), $param);

        return $this->xmlEncode($param, 'request');
    }

    /**
     * 读取返利计划商品库存参数解析
     *
     * @param array $param
     *
     * @return string
     */
    protected function parseReadSpkcParam($param)
    {
        $param = $this->handlerParam($this->arrayToKey(['itemid', 'fljhid', 'bdate',]), $param);

        return $this->xmlEncode($param, 'request');
    }

    /**
     * 新增会员档案参数解析
     *
     * @param array $param
     *
     * @return string
     */
    protected function parseAddHydaParam($param)
    {
        $dnums = count($param);
        $tiem = "";
        foreach ($param as $v) {
            $v = $this->handlerParam($this->arrayToKey([
                'customerid', 'name', 'phone', 'address', 'personid', 'mobilephone', 'birthday',
                'sex', 'edulevel', 'email', 'compname', 'cmpaddr', 'mantitle', 'yc_jf',
            ]), $v);

            $tiem .= $this->parseArrToXml($v);
        }

        return $this->parseRequestXml($tiem, $dnums);
    }

    /**
     * 修改会员档案参数解析
     *
     * @param array $param
     *
     * @return string
     */
    protected function parseSaveHydaParam($param)
    {
        // 家庭地址和单位地址不能同时修改，默认优先修改家庭地址
        if ($param['address']) {
            $param['cmpaddr'] = 'J' . $param['address'];
            unset($param['address']);
        } elseif ($param['cmpaddr']) {
            $param['cmpaddr'] = 'D' . $param['cmpaddr'];
        }

        $param = $this->handlerParam($this->arrayToKey([
            'customerid', 'name', 'phone', 'personid', 'mobilephone', 'birthday', 'sex',
            'edulevel', 'email', 'compname', 'cmpaddr', 'mantitle',
        ]), $param);

        return $this->parseRequestXml($this->parseArrToXml($param));
    }

    /**
     * 提交返利计划参数解析
     *
     * @param array $param
     *
     * @return string
     */
    protected function parseAddJfdhParam($param)
    {
        if (!isset($param['bdate'])) {
            $param['bdate'] = date('Y-m-d', time());
        }

        if (!isset($param['ytype'])) {
            $param['ytype'] = 'w';
        }

        return $this->parseRequestXml($this->parseArrToXml($param));
    }

    /**
     * 将Ehd请求数组解析为XML字符串
     *
     * @param array     $data
     * @param string    $dnums
     *
     * @return string
     */
    protected function parseRequestXml($data, $dnums='1')
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'.$this->parseArrToXml([
            'dnums' => $dnums,
            'data'  => $data,
        ], 'request');
    }

    /**
     * 将数组值转换为键名
     *
     * @param array  $arr
     * @param string $fill  填充数组值
     *
     * @return array
     */
    protected function arrayToKey($arr, $fill='')
    {
        return array_combine($arr, array_fill(0, count($arr), $fill));
    }

    /**
     * 将数组解析为XML字符串
     *
     * @param array     $arr
     * @param string    $rootItem
     *
     * @return string
     */
    protected function parseArrToXml(array $arr, $rootItem='item')
    {
        $xml = '<'.$rootItem.'>';

        foreach ($arr as $k => $v) {
            $xml .= '<'.$k.'>'.$v.'</'.$k.'>';
        }

        $xml .= '</'.$rootItem.'>';

        return $xml;
    }

    /**
     * 过滤读取Ehd请求返回结果
     *
     * @param array $result
     *
     * @return array
     *
     * @throws \Exception
     */
    protected function filterResult(array $result)
    {
        $resultCode     = (int) $result['resultCode'];
        $resultMessage  = $result['resultMessage'];

        $repose = [];

        switch ($resultCode) {
            case -1:
                e($resultMessage);
                break;
            case 0:
                break;
            case 1:
                $repose = [$resultMessage['item']];
                break;
            default:
                $repose = $resultMessage['item'];
        }

        return $repose;
    }

    /**
     * 过滤操作Ehd请求返回结果
     *
     * @param array $result
     *
     * @return int
     *
     * @throws \Exception
     */
    protected function filterPostResult(array $result)
    {
        $repose = 0;

        $resultCode     = (int) $result['resultCode'];
        $resultMessage  = $result['resultMessage'];

        if (-1 === $resultCode) {
            e($resultMessage);
        } else {
            $repose = (int) $resultMessage['item']['dq_type'];

            if (0 === $repose) {
                e($resultMessage['item']['errtext']);
            }
        }

        return $repose;
    }

    /**
     * XML编码
     * @param mixed $data 数据
     * @param string $root 根节点名
     * @param string $item 数字索引的子节点名
     * @param string $attr 根节点属性
     * @param string $id   数字索引子节点key转换的属性名
     * @param string $encoding 数据编码
     * @return string
     */
    protected function xmlEncode($data, $root='think', $item='item', $attr='', $id='id', $encoding='utf-8') {
        if(is_array($attr)){
            $_attr = array();
            foreach ($attr as $key => $value) {
                $_attr[] = "{$key}=\"{$value}\"";
            }
            $attr = implode(' ', $_attr);
        }
        $attr   = trim($attr);
        $attr   = empty($attr) ? '' : " {$attr}";
        $xml    = "<?xml version=\"1.0\" encoding=\"{$encoding}\"?>";
        $xml   .= "<{$root}{$attr}>";
        $xml   .= $this->dataToXml($data, $item, $id);
        $xml   .= "</{$root}>";
        return $xml;
    }

    /**
     * 数据XML编码
     * @param mixed  $data 数据
     * @param string $item 数字索引时的节点名称
     * @param string $id   数字索引key转换为的属性名
     * @return string
     */
    protected function dataToXml($data, $item='item', $id='id') {
        $xml = $attr = '';
        foreach ($data as $key => $val) {
            if(is_numeric($key)){
                $id && $attr = " {$id}=\"{$key}\"";
                $key  = $item;
            }
            $xml    .=  "<{$key}{$attr}>";
            $xml    .=  (is_array($val) || is_object($val)) ? $this->dataToXml($val, $item, $id) : $val;
            $xml    .=  "</{$key}>";
        }
        return $xml;
    }
}