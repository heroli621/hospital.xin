<?php
/*
 * 商品 模型
 * */
namespace app\model;
use think\Model;

class Goods extends Model{

    //重定义表名
    //protected $table="rc_goods";

    //主键
    protected $pk='id';

    //自动写入时间戳
    protected $autoWriteTimestamp = true;


    public function setPriceAttr($value){
        return intval($value*100);
    }


    public function setExpressFeeAttr($value){
        return intval($value*100);
    }

    public function getPriceAttr($value){
        return $value/100;
    }


    public function getExpressFeeAttr($value){
        return $value/100;
    }

    //关联模型
    public function content(){
        return $this->hasOne('GoodsContent')->bind(['detail'=>'content']);
    }

    public function classify(){
        return $this->belongsTo('GoodsClassify')->bind('classify_name');
    }

    public function level(){
        return $this->belongsTo('Level','vip_level','level_id')->bind('level_name');
    }

    public function spec(){
        return $this->hasOne('GoodsSpec')->bind('price,integral');
    }

    public function specs(){
        return $this->hasMany('GoodsSpec');
    }

}