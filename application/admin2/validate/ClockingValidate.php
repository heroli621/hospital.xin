<?php
/*
 * 考勤 验证器
 * */
namespace app\admin2\validate;

use think\Validate;

class ClockingValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
        'member_id'  => 'require',
        'affair_id'  => 'require',
        'fee'  => 'require|float',
        'des'  => 'max:512',
        'start_time'  => 'require',
    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [
        'member_id' => '员工必须选择',
        'affair_id'     => '事务必须选择',
        'fee.require'     => '扣减金额必须填写',
        'fee.float'     => '扣减金额只能填写数字',
        'des'     => '备注说明最多不能超过512个字符',
        'start_time'     => '起止时间必须选择',
    ];




}
