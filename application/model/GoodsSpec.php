<?php
/*
 * 商品规格 模型
 * */
namespace app\model;
use think\Model;

class GoodsSpec extends Model{

    //重定义表名
    //protected $table="rc_goods_spec";

    //主键
    protected $pk='id';

    //自动写入时间戳
    protected $autoWriteTimestamp = true;

    public function setPriceAttr($value){
        return $value*100;
    }

    public function getPriceAttr($value){
        return $value/100;
    }




}