<?php
/*
 * 订单商品列表 模型
 * */
namespace app\model;
use think\Model;

class OrdersGoods extends Model{

    //重定义表名
    //protected $table="rc_orders_goods";


    //主键
    protected $pk='id';

    //自动写入时间戳
    //protected $autoWriteTimestamp = true;

    public function setPriceAttr($value){
        return $value*100;
    }
    public function setSaleMoneyAttr($value){
        return $value*100;
    }


    public function getPriceAttr($value){
        return number_format($value/100,2);
    }
    public function getSaleMoneyAttr($value){
        return number_format($value/100,2);
    }


    public function goods(){
        return $this->belongsTo('Goods','goods_id','id');
    }
    public function orders(){
        return $this->belongsTo('Orders');
    }
    public function activity(){
        return $this->belongsTo('Activity');
    }

    public function spec(){
        return $this->belongsTo('GoodsSpec');
    }
}