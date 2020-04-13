<?php
/*
 * 会员 模型
 * */
namespace app\model;
use think\Model;

class Member extends Model{

    //重定义表名
    //protected $table="rc_member";

    //主键
    protected $pk='id';

    //自动写入时间戳
    protected $autoWriteTimestamp = true;

    public function getBalanceAttr($value){
        return number_format($value/100,2);
    }

    public function getRechargeMoneyAttr($value){
        return number_format($value/100,2);
    }

    public function getReturnMoneyAttr($value){
        return number_format($value/100,2);
    }

    public function getConsumptionMoneyAttr($value){
        return number_format($value/100,2);
    }

    public function getEarningsAttr($value){
        return number_format($value/100,2);
    }

    public function getSexAttr($value){
        $new_val='保密';
        switch ($value){
            case 1:
                $new_val='男';
                break;
            case 2:
                $new_val='女';
                break;
        }
        return $new_val;
    }

    public function getBirthdayAttr($value){

        return $value?date('Y-m-d',$value):'';
    }

    public function level(){
        return $this->belongsTo('Level','level_id','level_id')->bind('level_name');
    }
    public function staff(){
        return $this->belongsTo('Member','staff_id','id')->bind(['staff_name'=>'realname']);
    }

}