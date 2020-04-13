<?php
/*
 * 购物车 模型
 * */
namespace app\model;


use think\Model;

class MemberCart extends Model{

    //重定义表名
    //protected $table="rc_member_cart";

    //主键
    protected $pk='id';

    //自动写入时间戳
    protected $autoWriteTimestamp = true;

    protected $insert=['member_id'];

    public function setMemberIdAttr(){
        return session('member.mid');
    }

    //关联模型
    public function goods(){
        return $this->belongsTo('Goods');
    }

    public function goodsSpec(){
        return $this->belongsTo('GoodsSpec');
    }

    public function buy(){
        return $this->hasOne("OrdersGoods",'goods_id','goods_id')->bind(['buy_goods_num'=>'buy_num']);
    }

    public function activity(){
        return $this->belongsTo('Activity','goods_id','goods_id')->bind('limits,activity_type,sale');
    }

}