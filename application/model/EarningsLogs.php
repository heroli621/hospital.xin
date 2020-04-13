<?php
/*
 * 收益记录 模型
 * */
namespace app\model;
use think\Model;

class EarningsLogs extends Model{

    //重定义表名
    //protected $table="rc_earnings_logs";

    //主键
    protected $pk='id';

    //自动写入时间戳
    protected $autoWriteTimestamp = true;

    public function getMoneyAttr($value){
        return number_format($value/100,2);
    }

    //关联
    public function member(){
        return $this->belongsTo('Member','member_id','id')->bind('nickname');
    }


}