<?php
/*
 * 前台商品 模型
 * */
namespace app\model;
use think\Model;

class GoodsShop extends Model{

    //重定义表名
    protected $table="rc_goods";

    //主键
    protected $pk='id';

    //自动写入时间戳
    protected $autoWriteTimestamp = true;

    public function getImagesAttr($value){
        return explode(',',$value);
    }

    public function getPriceAttr($value){
        return $value/100;
    }

    public function getExpressFeeAttr($value){
        if($value){
            return $value/100;
        }else{
            return '包邮';
        }

    }

    //关联模型
    public function content(){
        return $this->hasOne('GoodsContent','goods_id','id')->bind(['detail'=>'content']);
    }

    public function spec(){
        return $this->hasOne('GoodsSpec','goods_id','id');
    }

    public function specs(){
        return $this->hasMany('GoodsSpec','goods_id','id');
    }

    public function activity(){
        return $this->hasOne('ActivityShop','goods_id','id');
    }

    public function level(){
        return $this->belongsTo('Level','vip_level','level_id')->bind('level_name');
    }

}