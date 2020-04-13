<?php
/*
 * 兑换码 验证器
 * */
namespace app\admin\validate;

use think\Validate;

class RedeemValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
        'num'  => 'require|number|between:1,1000',
        'learning_count'  => 'require|number',
    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [
        'num.require' => '兑换码生成数量必须填写',
        'num.number'     => '兑换码生成数量请填写正整数',
        'num.between'     => '兑换码生成数量最大1000条',
        'learning_count.require'     => '可兑换学习点必须填写',
        'learning_count.number'     => '学习点请填写正整数',
    ];

    //验证场景
    protected $scene = [
        'edit'  =>  ['course_classify_id','teacher'],
    ];







}
