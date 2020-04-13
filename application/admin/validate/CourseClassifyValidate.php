<?php
/*
 * 商品分类 验证器
 * */
namespace app\admin\validate;

use think\Validate;

class CourseClassifyValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
        'classify_name'  => 'require|max:64|unique:course_classify',
    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [
        'classify_name.require'     => '分类名称必须填写',
        'classify_name.max'     => '分类名称不能大于32字符',
        'classify_name.unique'     => '分类名称已经存在'
    ];



}
