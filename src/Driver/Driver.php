<?php
namespace Operator\Driver;

/**
 * 运营商认证接口
 * Interface Driver
 *
 * @package Operator\Driver
 */
interface Driver {

    /**
     * 获取认证URL
     * @Author   liuchao
     *
     * @param array $args
     *
     * @return mixed
     */
    public function getAuthUrl(array $args);

    /**
     * 获取详单信息
     * @Author   liuchao
     *
     * @param array $args
     *
     * @return mixed
     */
    public function getCall(array $args);
}
