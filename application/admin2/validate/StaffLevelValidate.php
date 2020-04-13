<?php
/*
 * 员工等级 验证器
 * */
namespace app\admin2\validate;

use think\Validate;

class StaffLevelValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
        'level'  => 'require|number|unique:staff_level',
        'level_name'  => 'require|max:255|unique:staff_level',
        'pay'  => 'require|float',
    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [
        'level.require' => '员工等级必须填写',
        'level.number'     => '员工等级只能填写数字',
        'level.unique'     => '已经存在相同的员工等级',
        'level_name.require' => '等级名称必须填写',
        'level_name.max'     => '等级名称最多不能超过255字符',
        'level_name.unique'     => '已经存在相同的员工等级名称',
        'pay.require' => '基本薪资必须填写',
        'pay.float'     => '基本薪资只能填写数字',
    ];

    protected function checkSale($value){
        return is_int($value)|| $value<1 || $value>100?false:true;
    }


    //验证场景
    protected $scene = [
        'level_name'  =>  ['level_name'],
        'pay'  =>  ['pay'],
    ];







}
