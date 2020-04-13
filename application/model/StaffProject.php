<?php
/*
 * 员工洗澡项目记录 模型
 * */
namespace app\model;
use think\Model;

class StaffProject extends Model{

    //重定义表名
    //protected $table="rc_staff_project";

    //主键
    protected $pk='id';

    //自动写入时间戳
    protected $autoWriteTimestamp = true;

    public function setFeeAttr($value){
        return intval($value*100);
    }

    public function getFeeAttr($value){
        return number_format($value/100,2);
    }

    public function member(){
        return $this->belongsTo("Member",'member_id','id')->bind('realname,phone');
    }

}