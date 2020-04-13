<?php
/*
 * 员工 模型
 * */
namespace app\model;
use think\Model;

class Staff extends Model{

    //重定义表名
    //protected $table="rc_staff";

    //主键
    protected $pk='id';

    //自动写入时间戳
    protected $autoWriteTimestamp = true;


    public function member(){
        return $this->belongsTo("Member",'member_id','id')->bind('realname,phone,sex,email,birthday,interests,openid,address,age,constellation,blood');
    }

    public function name(){
        return $this->belongsTo("Member",'member_id','id')->bind('realname,phone');
    }

    public function level(){
        return $this->belongsTo("StaffLevel",'staff_level','level')->bind('level_name,pay');
    }

    public function evaluate(){
        return $this->hasMany('ServerEvaluate','staff_id','member_id');
    }
}