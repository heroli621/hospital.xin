<?php
/*
 * 会员提现申请 模型
 * */
namespace app\model;
use think\Model;

class MemberWithdraw extends Model{

    //重定义表名
    //protected $table="rc_member_withdraw";

    //主键
    protected $pk='id';

    //自动写入时间戳
    protected $autoWriteTimestamp = true;

    //获取器
    public function getMoneyAttr($value)
    {
        return number_format($value/100,2);
    }
    public function getOldEarningsAttr($value)
    {
        return number_format($value/100,2);
    }
    public function getResidueEarningsAttr($value)
    {
        return number_format($value/100,2);
    }
    public function getCreateTimeAttr($value)
    {
        return $value?date('Y-m-d',$value):'';
    }
    public function getAuditTimeAttr($value)
    {
        return $value?date('Y-m-d',$value):'';
    }
    public function getSuccessTimeAttr($value)
    {
        return $value?date('Y-m-d',$value):'';
    }

    //关联模型
    public function member(){
        return $this->belongsTo('Member')->bind('nickname');
    }

    public function info(){
        return $this->belongsTo('Member');
    }

}