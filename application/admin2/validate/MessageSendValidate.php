<?php
/*
 * 消息发送 验证器
 * */
namespace app\admin2\validate;

use think\Validate;

class MessageSendValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
        'mobile'  => 'require',
        'message'  => 'require',
    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [
        'mobile'  => '会员手机必须填写',
        'message'     => '消息内容必须填写'
    ];









}
