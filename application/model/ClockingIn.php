<?php
/*
 * 优惠券 模型
 * */
namespace app\model;
use think\Model;

class ClockingIn extends Model{

    //重定义表名
    //protected $table="rc_coupon";

    protected $insert=['start_time','stop_time'];

    //主键
    protected $pk='id';

    //自动写入时间戳
    protected $autoWriteTimestamp = true;

    public function setFeeAttr($value){
        return intval($value*100);
    }


    public function getFeeAttr($value){
        return number_format($value/100,2);
    }




    //关联
    public function affair(){
        return $this->belongsTo('Affair')->bind('affair_name');
    }

    public function member(){
        return $this->belongsTo('Member')->bind('realname');
    }

}