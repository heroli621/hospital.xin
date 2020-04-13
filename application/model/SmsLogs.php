<?php
/*
 * 短信发送记录 模型
 * */
namespace app\model;
use think\Model;

class SmsLogs extends Model{

    //重定义表名
    //protected $table="rc_sms_logs";

    //主键
    protected $pk='id';

    //自动写入时间戳
    protected $autoWriteTimestamp = true;


    //关联
    public function member(){
        return $this->belongsTo('Member','member_id','id')->bind('nickname');
    }


}