<?php
/*
 * 文章 验证器
 * */
namespace app\admin2\validate;

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
        'title'  => 'require|max:255|unique:articles,title',
        'writer'  => 'max:255',
        'description'  => 'max:512',
        'content'  => 'requireCallback:isEmpty',
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
        'description'  => '描述不能超过500字符',
        'content'     => '文章内容必须填写',
    ];

    //验证场景
   /* protected $scene = [
        'edit'  =>  ['username','mail','des'],
        'save'  =>  ['username','psd','mail','des'],
    ];*/

    //自定义验证password
    protected function isEmpty($value,$data){
        if(empty($data['url'])){
            return true;
        }
        return false;
    }






}
