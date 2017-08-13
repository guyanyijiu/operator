<?php

namespace Operator\Driver;

use \Curl\Curl;

/**
 * 闪银运营商认证类
 * @Author   liuchao
 * Class Wecash
 * @package  Operator\Driver
 */
class Wecash implements Driver {

    /**
     * @var string 在闪银的商户号
     */
    private $source = source;

    /**
     * @var string 数据获取的密钥
     */
    private $token = token;

    /**
     * @var string H5授权方式的授权页面URL
     *             https://open.wecash.net/auth/genui/index.html#login/operator/{source}/{customer_id}?returnUrl={returnUrl}
     */
    private $h5_url = 'https://open.wecash.net/auth/genui/index.html#login/operator/%s/%s?returnUrl=%s';

    /**
     * @var string 获取通话详单的URL
     *             https://open.wecash.net/query/v1/{source}?client_customer_id={client_customer_id}&timestamp={timestamp}&signature={signature}
     */
    private $call_info_url = 'https://open.wecash.net/query/v1/%s?client_customer_id=%s&timestamp=%s&signature=%s';


    /**
     * 获取H5授权页面URL
     *
     * @Author   liuchao
     *
     * @param array $args   userid      用户ID
     *                      return_url  回调地址
     *
     * @return string
     */
    public function getAuthUrl(array $args){
        $args['userid'] = $this->disposeUserid($args['userid']);
        return sprintf($this->h5_url, $this->source, $args['userid'], urlencode($args['return_url']));
    }

    /**
     * 获取通话详单信息
     *
     * @Author   liuchao
     *
     * @param array $args   userid  用户ID
     *
     * @return array|bool
     */
    public function getCall(array $args){
        list($msec, $sec) = explode(' ', microtime());
        $timestamp = $sec . ceil($msec * 1000);

        $signature = $this->signature([
            $this->source,
            $args['userid'],
            $timestamp,
        ]);
        $url = sprintf($this->call_info_url, $this->source, $args['userid'], $timestamp, $signature);
        $curl = new Curl();
        $curl->get($url);
        if ($curl->error) {
            return false;
        } else {
            if($curl->response->code == 'E000000'){
                $ret = [
                    'basic_info' => json_encode($curl->response->data->transportation[0]->origin->base_info),
                    'bill_info' => json_encode($curl->response->data->transportation[0]->origin->bill_info),
                    'call_info' => json_encode($curl->response->data->transportation[0]->origin->call_info),
                ];

                $call_info = $curl->response->data->transportation[0]->origin->call_info;
                $call_count = 0;
                foreach($call_info->data as $list){
                    if(isset($list->details)){
                        $call_count += count($list->details);
                    }
                }
                $ret['call_count'] = $call_count;
                return $ret;
            }else{
                return false;
            }
        }
    }

    /**
     * 获取参数签名
     *
     * @Author   liuchao
     *
     * @param array $args
     *
     * @return string
     */
    private function signature($args = []){
        $args[] = $this->token;
        sort($args, SORT_STRING);
        return strtoupper(md5(implode('', $args)));
    }

    /**
     * 转换 userid 带中划线或者不带中划线
     *
     * @Author   liuchao
     *
     * @param      $userid
     * @param bool $bool
     *
     * @return mixed|string
     */
    private function disposeUserid($userid, $bool = true){
        if($bool){
            $ret = str_replace('-', '', $userid);
        }else{
            $ret = '';
            for($i = 0; $i < 32; $i++){
                $ret .= $userid[$i];
                if(in_array($i, [7, 11, 15, 19])){
                    $ret .= '-';
                }
            }
        }
        return $ret;
    }
}
