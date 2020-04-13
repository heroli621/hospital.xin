<?php
/*
 * 充值卡 验证器
 * */
namespace app\admin\validate;

use think\Validate;

class RechargeValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
        'goods_name'  => 'require|max:255',
        'pay_money'  => 'number',
        'learning_count'  => 'number',
    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [
        'goods_name.require' => '充值卡名称必须填写',
        'goods_name.max'     => '充值卡名称过长',
        'pay_money'     => '充值金额只能填写整数',
        'learning_count'     => '学习点只能填写整数'
    ];



}
