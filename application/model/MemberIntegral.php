<?php
/*
 * 会员积分记录 模型
 * */
namespace app\model;
use think\Model;

class MemberIntegral extends Model{

    //重定义表名
    //protected $table="rc_level";

    //主键
    protected $pk='id';

    //自动写入时间戳
    protected $autoWriteTimestamp = true;


    public function getCreateTimeAttr($value)
    {
        return date('Y-m-d',$value);
    }

    //关联模型
    public function member(){
        return $this->belongsTo('Member')->bind('nickname');
    }

}