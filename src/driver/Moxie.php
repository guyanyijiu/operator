<?php

namespace Operator\Driver;

use GuzzleHttp\Client;
use GuzzleHttp\Promise;

/**
 * 魔蝎运营商认证类
 * @Author   liuchao
 * Class Moxie
 * @package  Operator\Driver
 */
class Moxie implements Driver {

    /**
     * @var string 魔蝎平台apikey
     */
    private $apiKey;

    /**
     * @var string 魔蝎平台token
     */
    private $token;

    /**
     * @var string H5授权方式的授权页面URL
     */
    private $h5_url = 'https://api.51datakey.com/h5/importV3/index.html#/carrier?apiKey=%s&userId=%s&backUrl=%s&themeColor=FF0000';

    /**
     * @var string 获取通话详单的URL
     */
    private $call_info_url = 'https://api.51datakey.com/carrier/v3/mobiles/%s/call?month=&task_id=%s';

    /**
     * @var string 获取基本信息的URL
     */
    private $basic_info_url = 'https://api.51datakey.com/carrier/v3/mobiles/%s/basic?task_id=%s';

    /**
     * @var string 获取账单的URL
     */
    private $bill_info_url = 'https://api.51datakey.com/carrier/v3/mobiles/%s/bill?task_id=%s&from_month=%s&to_month=%s';

    /**
     * @var string 获取所有数据的URL
     */
//    private $all_info_url = 'https://api.51datakey.com/carrier/v3/mobiles/%s/mxdata?task_id=%s';

    /**
     * 设置 apikey 和 token
     *
     * Moxie constructor.
     *
     * @param array $args
     */
    public function __construct($args = []){
        if(!isset($args['apikey']) || !isset($args['token'])){
            throw new \InvalidArgumentException('no apikey and token');
        }
        $this->apiKey = $args['apikey'];
        $this->token = $args['token'];
    }

    /**
     * 获取H5授权页面URL
     *
     * @Author   liuchao
     *
     * @param array $args   userid      用户ID
     *                      return_url  回调URL
     *                      以下三个必须同时传或者不传，如果不传，则需要用户在授权页面填写
     *                      phone       手机号
     *                      name        名字
     *                      idcard      身份证号
     *
     *
     * @return string
     */
    public function getAuthUrl(array $args){
        if($args['phone'] && $args['name'] && $args['idcard']){
            return sprintf($this->h5_url, $this->apiKey, $args['userid'], urlencode($args['return_url'])) . sprintf('&loginParams={"phone":"%s","name":"%s","idcard":"%s"}', $args['phone'], $args['name'], $args['idcard']);
        }
        return sprintf($this->h5_url, $this->apiKey, $args['userid'], urlencode($args['return_url']));
    }

    /**
     * 获取通话详单信息
     *
     * @Author   liuchao
     *
     * @param array $args   phone   手机号
     *                      task_id 任务ID（魔蝎回调时回传）
     *
     * @return array
     */

    public function getCall(array $args){

        $from_month = date('Y-m', strtotime('-5 month'));
        $to_month = date('Y-m');

        $basic_info_url = sprintf($this->basic_info_url, $args['phone'], $args['task_id']);
        $bill_info_url = sprintf($this->bill_info_url, $args['phone'], $args['task_id'], $from_month, $to_month);
        $call_info_url = sprintf($this->call_info_url, $args['phone'], $args['task_id']);

        $ret = [];

        $client = new Client([
            'headers' => [
                'Authorization' => "apikey {$this->apiKey}",
                'Authorization' => "token {$this->token}"
            ]
        ]);
        $promises = [
            'basic_info' => $client->getAsync($basic_info_url),
            'bill_info' => $client->getAsync($bill_info_url),
            'call_info' => $client->getAsync($call_info_url),
        ];

        $results = Promise\unwrap($promises);

        $ret['basic_info'] = $results['basic_info']->getBody()->getContents();
        $ret['bill_info'] = $results['bill_info']->getBody()->getContents();
        $ret['call_info'] = $results['call_info']->getBody()->getContents();

        if($ret['basic_info'] && $ret['bill_info'] && $ret['call_info']){
            $call_info = json_decode($ret['call_info'], true);
            $call_count = 0;
            foreach($call_info['list'] as $list){
                $call_count += count($list['calls']);
            }
            $ret['call_count'] = $call_count;
            return $ret;
        }

        return false;
    }


}
