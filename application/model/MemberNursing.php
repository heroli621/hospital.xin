<?php
/*
 * 护理产品记录 模型
 * */
namespace app\model;
use think\Model;

class MemberNursing extends Model{

    //重定义表名
    //protected $table="rc_member_nursing";

    //主键
    protected $pk='id';

    //自动写入时间戳
    protected $autoWriteTimestamp = true;


    //关联模型
    public function orders(){
        return $this->belongsTo('Orders','order_id','id');
    }

    public function member(){
        return $this->belongsTo('Member','member_id','id');
    }

    public function goods(){
        return $this->belongsTo('Goods','goods_id','id');
    }

    public function gname(){
        return $this->belongsTo('Goods','goods_id','id')->bind('goods_name');
    }

}