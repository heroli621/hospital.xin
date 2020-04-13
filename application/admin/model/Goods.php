<?php
/*
 * 商品 模型
 * */
namespace app\admin\model;


use think\Model;
use think\model\concern\SoftDelete;

class Goods extends Model{
    use SoftDelete;
    protected $deleteTime = "delete_time";

    //重定义表名
    //protected $table="hy_goods";

    //主键
    protected $pk='goods_id';

    //自动写入时间戳
    protected $autoWriteTimestamp = true;

    public function setImagesAttr($value){
        return serialize($value);
    }

    public function getImagesAttr($value){
        return $value?unserialize($value):[];
    }

}