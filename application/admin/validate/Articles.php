<?php
/*
 * 文章 验证器
 * */
namespace app\admin\validate;

use think\Validate;

class Articles extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
        'title'  => 'require|max:255',
        'writer'  => 'max:255',
        'des'  => 'max:512',
    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [
        'title.require' => '标题必须填写',
        'title.max'     => '标题最多不能超过255个字符',
        'title.unique'     => '文章标题已发布',
        'writer'     => '作者不能超过255字符',
        'des'  => '描述不能超过500字符',
    ];







}
