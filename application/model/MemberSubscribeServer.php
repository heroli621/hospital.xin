<?php
/*
 * 会员预约服务项目 模型
 * */
namespace app\model;

use think\Db;
use think\Model;

class MemberSubscribeServer extends Model{

    //重定义表名
    //protected $table="rc_member_subscribe_server";

    //主键
    protected $pk='id';


    //自动写入时间戳
    protected $autoWriteTimestamp = true;

    public function subscribe(){
        return $this->belongsTo("MemberSubscribe");
    }

    public function staff(){
        return $this->belongsTo("Member",'staff_id','id')->bind(['staff_name'=>'realname']);
    }

    public function servers(){
        return $this->belongsTo("Servers");
    }

}