<?php
/*
 * 商品分类 模型
 * */
namespace app\model;
use think\Model;

class GoodsClassify extends Model{

    //重定义表名
    //protected $table="rc_goods_classify";

    //主键
    protected $pk='id';

    //自动写入时间戳
    protected $autoWriteTimestamp = true;

    /*//获取器
     * public function getReleaseTimeAttr($value)
    {
        return date('Y-m-d',$value);
    }*/



}