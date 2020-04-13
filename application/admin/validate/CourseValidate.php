<?php
/*
 * 课程 验证器
 * */
namespace app\admin\validate;

use think\Validate;

class CourseValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
        'course_name'  => 'require|max:255|unique:course,course_name,1,is_delete',
        'course_classify_id'  => 'require',
        'teacher'  => 'require',
    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [
        'course_name.require' => '课程名称必须填写',
        'course_name.max'     => '课程名称最多不能超过255个字符',
        'course_name.unique'     => '课程名称已存在',
        'course_classify_id'     => '请选择课程分类',
        'teacher'  => '请选择讲师',
    ];

    //验证场景
    protected $scene = [
        'edit'  =>  ['course_classify_id','teacher'],
    ];







}
