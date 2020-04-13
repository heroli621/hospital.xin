<?php
/*
 * 事务 验证器
 * */
namespace app\admin2\validate;

use think\Validate;

class AffairValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
        'affair_name'  => 'require|max:128',
        'fee'  => 'require|float',
    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [
        'affair_name.require' => '事务名称必须填写',
        'affair_name.max'     => '事务名称最多不能超过128个字符',
        'fee.require'     => '扣减金额必须填写',
        'fee.float'     => '扣减金额只能填入数字',
    ];

    //验证场景
    protected $scene = [
        'affair_name'  =>  ['affair_name'],
        'fee'  =>  ['fee'],
    ];






}
