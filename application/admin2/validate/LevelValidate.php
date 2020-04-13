<?php
/*
 * 会员等级 验证器
 * */
namespace app\admin2\validate;

use think\Validate;

class LevelValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
        'level_id'  => 'require|number|max:5|unique:level',
        'level_name'  => 'require|max:255|unique:level',
        'level_money'  => 'require|number',
        'level_sale'  => 'require|checkSale',
        'level_condition'  => 'max:1000',
        'level_interests'  => 'max:1000',
    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [
        'level_id.require' => '会员等级必须填写',
        'level_id.number'     => '会员等级只能填写数字',
        'level_id.max'     => '会员等级最多不能超过5位数',
        'level_id.unique'     => '已经存在相同的会员等级',
        'level_name.require' => '等级名称必须填写',
        'level_name.max'     => '等级名称最多不能超过255字符',
        'level_name.unique'     => '已经存在相同的会员等级名称',
        'level_money.require' => '等级所需累计充值金额必须填写',
        'level_money.number'     => '等级所需累计充值金额只能填写数字',
        'level_sale.require' => '享受的折扣必须填写',
        'level_sale.checkSale'     => '享受的折扣只能填写1-100的数字',
        'level_condition'     => '等级条件描述不能超过1000字符',
        'level_interests'     => '等级权益描述不能超过1000字符',
    ];

    protected function checkSale($value){
        return is_int($value)|| $value<1 || $value>100?false:true;
    }


    //验证场景
    protected $scene = [
        'level'  =>  ['level'],
        'level_name'  =>  ['level_name'],
        'level_money'  =>  ['level_money'],
        'level_condition'  =>  ['level_condition'],
        'level_interests'  =>  ['level_interests'],
        'level_sale'  =>  ['level_sale'],
    ];







}
