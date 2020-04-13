<?php
/*
 * 提现记录    模型
 * */
namespace app\admin\model;
use think\Model;

class Withdraw extends Model{

    //重定义表名
    //protected $table="hy_withdraw";

    //主键
    protected $pk='withdraw_id';

    //自动写入时间戳
    protected $autoWriteTimestamp = true;



    public function member(){
        return $this->belongsTo("Member","member_id","member_id")->bind(['nickname','mobile']);
    }


}