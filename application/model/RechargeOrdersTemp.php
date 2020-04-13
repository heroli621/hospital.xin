<?php
/*
 * 手动充值订单临时返现信息 模型
 * */
namespace app\model;


use think\Db;
use think\Model;

class RechargeOrdersTemp extends Model{

    //重定义表名
    //protected $table="rc_recharge_orders_temp";

    //主键
    protected $pk='id';


    //自动写入时间戳
    //protected $autoWriteTimestamp = true;

    public function getReturnMoneyAttr($value){
        return $value/100;
    }

}