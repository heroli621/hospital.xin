<?php
/*
 * 预约服务 模型
 * */
namespace app\model;


use think\Db;
use think\Model;

class Servers extends Model{

    //重定义表名
    //protected $table="rc_servers";

    //主键
    protected $pk='id';


    //自动写入时间戳
    protected $autoWriteTimestamp = true;

    public function setCostAttr($value){
        return intval($value*100);
    }
    public function setFeeAttr($value){
        return intval($value*100);
    }

    public function getCostAttr($value){

        return number_format($value/100,2);

    }

    public function getFeeAttr($value){

        return number_format($value/100,2);

    }


}