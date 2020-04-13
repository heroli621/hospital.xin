<?php
/*
 * 学员 验证器
 * */
namespace app\admin\validate;

use think\Validate;

class MemberValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
        'phone'  => 'require|mobile|unique:member',
    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [
        'phone.require' => '手机号未填写',
        'phone.mobile'     => '手机号格式错误',
        'phone.unique'     => '手机号已注册',
    ];








}
