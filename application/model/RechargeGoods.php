<?php
/*
 * 充值卡 模型
 * */
namespace app\model;
use think\Model;

class RechargeGoods extends Model{

    //重定义表名
    //protected $table="rc_recharge_goods";

    //主键
    protected $pk='id';

    //自动写入时间戳
    protected $autoWriteTimestamp = true;

    public function setMoneyAttr($value)
    {
        return intval($value*100);
    }
    public function setReturnMoneyAttr($value)
    {
        return intval($value*100);
    }


    public function getMoneyAttr($value){
        return $value/100;
    }
    public function getReturnMoneyAttr($value){
        return $value/100;
    }


}