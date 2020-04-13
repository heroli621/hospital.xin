<?php
/*
 * 优惠券领取记录 模型
 * */
namespace app\model;
use think\Model;

class CouponGet extends Model{

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
        return $this->belongsTo('Coupon')->bind(['coupon_name','start_time','stop_time','need_money','sale_money','get_amount']);
    }

    public function coupons(){
        return $this->belongsTo('Coupon')->joinType('Right');
    }

    public function memberUse(){
        return $this->hasOne('CouponUse')->bind(['use_time'=>'create_time']);
    }

}