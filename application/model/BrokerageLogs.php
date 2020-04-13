<?php
/*
 * 佣金记录 模型
 * */
namespace app\model;
use think\Model;

class BrokerageLogs extends Model{

    //重定义表名
    //protected $table="rc_brokerage_logs";

    //主键
    protected $pk='id';

    //自动写入时间戳
    protected $autoWriteTimestamp = true;

    public function getOrderMoneyAttr($value){
        return number_format($value/100,2);
    }
    public function getBrokerageAttr($value){
        return number_format($value/100,2);
    }

    //关联
    public function member(){
        return $this->belongsTo('Member','member_id','id')->bind('nickname');
    }

    public function fans(){
        return $this->belongsTo('Member','child_id','id')->bind(['fans_name'=>'nickname']);
    }

}