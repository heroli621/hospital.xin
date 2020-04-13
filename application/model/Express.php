<?php
/*
 * 物流公司 模型
 * */
namespace app\model;
use think\Model;

class Express extends Model{

    //重定义表名
    //protected $table="rc_express";

    //主键
    protected $pk='id';

    //自动写入时间戳
    protected $autoWriteTimestamp = true;


    public function setComAttr($value){
        return strtolower($value);
    }


}