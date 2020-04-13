<?php
/*
 * 异常订单 模型
 * */
namespace app\model;


use think\Db;
use think\Model;

class OrdersErr extends Model{

    //重定义表名
    //protected $table="rc_orders_err";

    //主键
    protected $pk='id';

    //自动写入时间戳
    protected $autoWriteTimestamp = true;


    //关联模型
    //会员
    public function member(){
        return $this->belongsTo('Member')->bind('nickname');
    }

    //订单
    public function orders(){
        return $this->belongsTo('Orders');
    }

}