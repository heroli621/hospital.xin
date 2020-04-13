<?php
/*
 * 会员等级 模型
 * */
namespace app\model;
use think\Model;

class Level extends Model{

    //重定义表名
    //protected $table="rc_level";

    //主键
    protected $pk='id';

    //自动写入时间戳
    //protected $autoWriteTimestamp = true;

    public function setLevelMoneyAttr($value){
        return $value*100;
    }
    //获取器
    public function getLevelMoneyAttr($value)
    {
        return $value/100;
    }



}