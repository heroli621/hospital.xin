<?php
/*
 * 提现账户验证 验证器
 * */
namespace app\shop\validate;

use think\Validate;

class AccountValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
        'realname'  => 'require|max:32',
        'account'  => 'require|max:128'
    ];


    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [
        'realname.require' => '真实姓名必须填写',
        'realname.max' => '真实姓名无效',
        'account.require'  => '账号、卡号必须填写',
        'account.max'  => '账号、卡号无效',
    ];









}
