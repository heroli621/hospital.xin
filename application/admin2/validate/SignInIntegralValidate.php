<?php
/*
 * 签到设置 验证器
 * */
namespace app\admin2\validate;

use think\Validate;

class SignInIntegralValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
        'level'  => 'require|number|max:5|unique:sign_in_integral',
        'continuous'  => 'require|number',
        'integral'  => 'require|number',
    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [
        'level.unique' => '已存在相同的等级',
        'continuous.number'     => '连续签到天数只能填写数字',
        'level.max'     => '等级最多不能超过5位数',
        'continuous.require' => '连续签到天数必须填写',
        'integral.require' => '可获积分必须填写',
        'integral.number'     => '可获积分只能填写数字'
    ];


    //验证场景
    protected $scene = [
        'continuous'  =>  ['continuous'],
        'integral'  =>  ['integral']
    ];







}
