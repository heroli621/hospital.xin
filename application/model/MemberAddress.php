<?php
/*
 * 收货地址 模型
 * */
namespace app\model;


use think\Model;

class MemberAddress extends Model{

    //重定义表名
    //protected $table="rc_member_address";

    //主键
    protected $pk='id';

    //自动写入时间戳
    protected $autoWriteTimestamp = true;

    protected $insert=['member_id'];

    public function setMemberIdAttr(){
        return session('member.mid');
    }



}