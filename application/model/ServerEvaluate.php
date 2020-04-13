<?php
/*
 * 预约服务评价 模型
 * */
namespace app\model;

use think\Db;
use think\Model;

class ServerEvaluate extends Model{

    //重定义表名
    //protected $table="rc_server_evaluate";

    //主键
    protected $pk='id';


    //自动写入时间戳
    protected $autoWriteTimestamp = true;

    public function member(){
        return $this->belongsTo("Member")->bind('nickname');
    }

    public function staff(){
        return $this->belongsTo("Member",'staff_id','id')->bind('realname');
    }

    public function server(){
        return $this->belongsTo("MemberSubscribeServer")->bind('server_name');
    }

}