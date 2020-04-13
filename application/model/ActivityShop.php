<?php
/*
 * 优惠活动前端用 模型
 * */
namespace app\model;


use think\Db;
use think\Model;

class ActivityShop extends Model{

    //重定义表名
    protected $table="rc_activity";

    //主键
    protected $pk='id';


    //自动写入时间戳
    //protected $autoWriteTimestamp = true;

    public function setStartTimeAttr($value,$data){
        $tt=explode(' _ ',$data['start_stop']);

        return strtotime($tt[0]);
    }

    public function setStopTimeAttr($value,$data){
        $tt=explode(' _ ',$data['start_stop']);

        return strtotime($tt[1]);
    }

    public function getStartTimeAttr($value,$data){
        $new_val=$value?date('Y/m/d-',$value).date('Y/m/d',$data['stop_time']):'';
        return $new_val;
    }

    public function getSaleAttr($value,$data){
        if($data['activity_type']==4){
            return Db::name('coupon')->where('id',$value)->value('coupon_name');
        }else{
            return $value;
        }
    }

    //关联模型
    public function coupon(){
        return $this->belongsTo('Coupon','id','sale')->bind('coupon_name');
    }

    public function goodsName(){
        return $this->belongsTo('Goods','goods_id','id')->bind('goods_name');
    }

    public function goods(){
        return $this->belongsTo('Goods','goods_id','id');
    }

}