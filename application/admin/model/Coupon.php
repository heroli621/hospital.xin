<?php
/*
 * 优惠券 模型
 * */
namespace app\admin\model;


use think\Model;

class Coupon extends Model{

    //重定义表名
    //protected $table="hy_coupon";

    //主键
    protected $pk='coupon_id';

    //自动写入时间戳
    protected $autoWriteTimestamp = true;

    public function classify(){
        return $this->belongsTo("Classify","classify_id","classify_id")->bind("classify_name");
    }

}