<?php
/*
 * 班级 验证器
 * */
namespace app\admin\validate;

use think\Validate;

class TermValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
        'term_name'  => 'require|max:255',
        'learning_count'  => 'number',
        'class_teacher'  => 'require|max:32',
        'wechart'  => 'require|max:128',
        'time_long'  => 'require',
        'renewal_long'  => 'require|number',
        'weekday'  => 'require|number',
        'cover'  => 'require',
        'des'  => 'require',
        'backdrop'  => 'require',
        'rules'  => 'require',
        'start_time'  => 'require',
    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [
        'term_name.require' => '班级名称必须填写',
        'term_name.max'     => '班级名称过长',
        'learning_count'     => '学习点只能填写整数',
        'class_teacher.require'  => '班主任姓名必须填写',
        'wechart.require'  => '班主任微信号必须填写',
        'time_long'  => '课程总时长必须填写',
        'renewal_long.require'  => '更新时长必须填写',
        'weekday.require'  => '更新周期必须填写',
        'cover'  => '封面名必须上传',
        'des'  => '简介必须填写',
        'backdrop'     => '课程背景必须填写',
        'rules'     => '报名细则必须填写',
        'start_time'     => '开班时间必须选择',
    ];


}
