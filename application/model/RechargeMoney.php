<?php
/*
 * 充值返现订单 模型
 * */
namespace app\model;
use think\Model;

class RechargeMoney extends Model{

    //重定义表名
    //protected $table="rc_recharge_money";

    //主键
    protected $pk='id';

    //自动写入时间戳
    protected $autoWriteTimestamp = true;

    public function getResidueMoneyAttr($value){
        return number_format($value/100,2);
    }
    public function getReturnMoneyAttr($value){
        return number_format($value/100,2);
    }


    public function member(){
        return $this->belongsTo("Member")->bind('nickname');
    }

    public function recharge(){
        return $this->belongsTo("RechargeGoods")->bind('money');
    }
}