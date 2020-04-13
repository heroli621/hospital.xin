<?php
/*
 * 员工等级 模型
 * */
namespace app\model;


use think\Db;
use think\Model;

class StaffLevel extends Model{

    //重定义表名
    //protected $table="rc_staff_level";

    //主键
    protected $pk='id';


    //自动写入时间戳
    protected $autoWriteTimestamp = true;

    public function setPayAttr($value){
        return intval($value*100);
    }

    public function getPayAttr($value){
        return number_format($value/100,2);
    }


}