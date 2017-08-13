<?php

namespace Operator;

/**
 * 运营商认证类
 * @Author   liuchao
 * Class Operator
 * @package  Operator
 */
class Operator {

    private static $_instance;

    public function __construct($type, $args = []){
        if($type){
            $class = '\\Operator\\Driver\\' . ucwords(strtolower($type));
            if(class_exists($class)){
                self::$_instance = new $class($args);
                return;
            }
        }
        throw new \InvalidArgumentException('参数错误');
    }

    public function __call($method, $arguments){
        return call_user_func_array([self::$_instance, $method], $arguments);
    }

}
