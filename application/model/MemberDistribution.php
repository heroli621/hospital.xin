<?php
/*
 * 会员分销 模型
 * */
namespace app\model;
use think\Model;

class MemberDistribution extends Model{

    //重定义表名
    //protected $table="rc_member_distribution";

    //主键
    protected $pk='id';

    //自动写入时间戳
    protected $autoWriteTimestamp = true;


    //粉丝信息
    public function member(){
        return $this->belongsTo('Member')->bind(['fans_name'=>'nickname','fans_headimg'=>'headimg']);
    }

    //一级信息
    public function oneLevel(){
        return $this->belongsTo('Member','up_one_id','id');
    }

    //二级信息
    public function twoLevel(){
        return $this->belongsTo('Member','up_two_id','id');
    }


}