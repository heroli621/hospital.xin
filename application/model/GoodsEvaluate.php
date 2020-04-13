<?php
/*
 * 商品评价 模型
 * */
namespace app\model;
use think\Model;

class GoodsEvaluate extends Model{

    //重定义表名
    //protected $table="rc_goods_evaluate";

    //主键
    protected $pk='id';

    //自动写入时间戳
    protected $autoWriteTimestamp = true;

    public function getImagesAttr($value){
        return explode(',',$value);
    }

    public function member(){
        return $this->belongsTo('Member')->bind('nickname,headimg');
    }


}