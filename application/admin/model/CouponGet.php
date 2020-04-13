<?php
/*
 * 优惠券获取记录 模型
 * */
namespace app\admin\model;


use think\Model;

class CouponGet extends Model{

    //重定义表名
    //protected $table="hy_coupon_get";

    //主键
    protected $pk='id';

    //自动写入时间戳
    protected $autoWriteTimestamp = true;

    //关联模型
    public function member(){
        return $this->belongsTo('Member',"member_id","member_id")->bind('nickname');
    }

    public function coupon(){
        return $this->belongsTo('Coupon',"coupon_id","coupon_id")->bind(['coupon_name','need_money','sale_money']);
    }

    public function employ(){
        return $this->hasOne('CouponUse')->bind(['use_time'=>'create_time']);
    }

}