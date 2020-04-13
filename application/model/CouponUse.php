<?php
/*
 * 优惠券使用记录 模型
 * */
namespace app\model;
use think\Model;

class CouponUse extends Model{

    //重定义表名
    //protected $table="rc_coupon_get";

    //主键
    protected $pk='id';

    //自动写入时间戳
    protected $autoWriteTimestamp = true;


    //关联模型
    public function member(){
        return $this->belongsTo('Member')->bind('nickname');
    }

    public function coupon(){
        return $this->belongsTo('Coupon')->bind(['coupon_name','start_time','stop_time','sale_money','need_money']);
    }

    public function couponGet(){
        return $this->belongsTo('CouponGet')->bind('validity');
    }

    public function orders(){
        return $this->belongsTo('Orders')->bind('money');
    }

}