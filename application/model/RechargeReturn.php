<?php
/*
 * 返现记录 模型
 * */
namespace app\model;
use think\Model;

class RechargeReturn extends Model{

    //重定义表名
    //protected $table="rc_recharge_return";

    //主键
    protected $pk='id';

    //自动写入时间戳
    protected $autoWriteTimestamp = true;

    public function getReturnMoneyAttr($value){
        return number_format($value/100,2);
    }

    //关联模型
    public function orders(){
        return $this->belongsTo('RechargeMoney');
    }

    public function member(){
        return $this->belongsTo("Member")->bind('nickname');
    }

}