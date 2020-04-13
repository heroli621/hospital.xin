<?php
/*
 * 优惠券 模型
 * */
namespace app\model;
use think\Model;

class Coupon extends Model{

    //重定义表名
    //protected $table="rc_coupon";

    protected $insert=['start_time','stop_time'];

    //主键
    protected $pk='id';

    //自动写入时间戳
    protected $autoWriteTimestamp = true;

    public function setNeedMoneyAttr($value){
        return $value*100;
    }
    public function setSaleMoneyAttr($value){
        return $value*100;
    }
    public function setStartTimeAttr($value,$data){
        if(!empty($data['validity_date'])){
            $tt=explode(' _ ',$data['validity_date']);

            return strtotime($tt[0]);
        }
        return 0;
    }
    public function setStopTimeAttr($value,$data){
        if(!empty($data['validity_date'])){
            $tt=explode(' _ ',$data['validity_date']);

            return strtotime($tt[1].' 23:59:59');
        }

        return 0;
    }


    public function getNeedMoneyAttr($value){
        return $value/100;
    }
    public function getSaleMoneyAttr($value){
        return $value/100;
    }



    //关联
    public function level(){
        return $this->belongsTo('Level','member_level','level_id')->bind('level_name');
    }

    public function memberGet(){
        return $this->hasMany('CouponGet','coupon_id','id');
    }

}