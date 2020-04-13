<?php
/*
 * 发货单 模型
 * */
namespace app\model;
use think\Model;

class Delivery extends Model{

    //重定义表名
    //protected $table="rc_delivery";

    //主键
    protected $pk='id';

    //自动写入时间戳
    protected $autoWriteTimestamp = true;



    //关联
    public function express(){
        return $this->belongsTo('Express','express_id','id')->bind('express_name');
    }

    public function member(){
        return $this->belongsTo('Member')->bind('nickname');
    }


}