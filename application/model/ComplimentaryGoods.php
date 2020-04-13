<?php
/*
 * 赠品 模型
 * */
namespace app\model;


use think\Model;

class ComplimentaryGoods extends Model{

    //重定义表名
    //protected $table="rc_complimentary_goods";

    //主键
    protected $pk='id';

    //自动写入时间戳
    protected $autoWriteTimestamp = true;


    //关联模型
    public function gname(){
        return $this->belongsTo('Goods','child_id','id')->bind('goods_name');
    }

    public function goods(){
        return $this->belongsTo('Goods','child_id','id');
    }
}