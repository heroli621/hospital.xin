<?php
/*
 * 预约服务 模型
 * */
namespace app\model;
use think\Model;

class MemberSubscribe extends Model{

    //重定义表名
    //protected $table="rc_member_subscribe";

    //主键
    protected $pk='id';

    //自动写入时间戳
    protected $autoWriteTimestamp = true;


    //关联模型

    public function member(){
        return $this->belongsTo('Member')->bind('nickname,phone,openid,realname');
    }

    public function goods(){
        return $this->hasMany("MemberSubscribeGoods");
    }

}