<?php

namespace Operator\Driver;

use \Curl\MultiCurl;

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
    private $apiKey = apiKey;

    /**
     * @var string 魔蝎平台token
     */
    private $token = token;

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
        $multi_curl = new MultiCurl();

        $multi_curl->setHeader('Authorization', "apikey {$this->apiKey}");
        $multi_curl->setHeader('Authorization', "token {$this->token}");

        $multi_curl->success(function($instance) use (&$ret, $basic_info_url, $bill_info_url, $call_info_url) {

            switch ($instance->url){
                case $basic_info_url:
                    $ret['basic_info'] = gzdecode($instance->response);
                    break;
                case $bill_info_url:
                    $ret['bill_info'] = gzdecode($instance->response);
                    break;
                case $call_info_url:
                    $ret['call_info'] = gzdecode($instance->response);
                    break;
            }

        });

        $multi_curl->addGet($basic_info_url);
        $multi_curl->addGet($bill_info_url);
        $multi_curl->addGet($call_info_url);

        $multi_curl->start();

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
