<?php
/*
 * 管理员 验证器
 * */
namespace app\admin2\validate;

use think\Validate;

class Admin extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
        'username'  => 'require|max:20',
        'phone'  => 'require|mobile|unique:user,mobile',
        'psd'  => 'require|password:/^[a-zA-Z0-9_]{6,16}$/',
        'mail'  => 'email|unique:user,email',
        'des'  => 'max:512',
    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [
        'username.require' => '姓名必须填写',
        'username.max'     => '姓名最多不能超过20个字符',
        'phone.require'     => '手机号码必须填写',
        'phone.mobile'     => '请输入有效的手机号码',
        'phone.unique'     => '手机号码已被注册',
        'mail.email'     => '请输入有效的邮箱地址',
        'mail.unique'     => '邮箱地址已被注册',
        'psd.require'     => '密码必须填写',
        'psd.password'     => '密码格式错误',
        'des'     => '备注不能超过500字符',
    ];

    //验证场景
    protected $scene = [
        'edit'  =>  ['username','mail','des'],
        'save'  =>  ['username','psd','mail','des'],
        'resetpsd'=>['psd']
    ];

    //自定义验证password
    protected function password($value,$rule,$data=[]){
        if(preg_match($rule,$value)){
            return true;
        }

        return false;
    }


}
