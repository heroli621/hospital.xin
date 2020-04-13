<?php
/*
 * 会员预约服务护理产品 模型
 * */
namespace app\model;

use think\Db;
use think\Model;

class MemberSubscribeGoods extends Model{

    //重定义表名
    //protected $table="rc_member_subscribe_goods";

    //主键
    protected $pk='id';


    //自动写入时间戳
    protected $autoWriteTimestamp = true;

    public function getCostAttr($value){
        return $value/100;
    }

    public function subscribe(){
        return $this->belongsTo("MemberSubscribe");
    }

    public function staff(){
        return $this->belongsTo("Member",'staff_id','id')->bind(['staff_name'=>'realname']);
    }

    public function member(){
        return $this->belongsTo("Member",'member_id','id');
    }

    public function nursing(){
        return $this->belongsTo("MemberNursing");
    }

}