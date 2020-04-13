<?php
/*
 * banner图 验证器
 * */
namespace app\admin\validate;

use think\Validate;

class Banner extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
         'title'  => 'max:255',
         //'url'  => 'url',
         'cover'  => 'require',
    ];

    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [
        'title.max'     => '标题最多不能超过255个字符',
        'url.url'     => '请输入有效的http链接',
        'cover.require'     => 'BANNER图片必须上传',
    ];


    // edit 验证场景定义
    /*protected $scene = [
        'edit'  =>  ['name','age'],
    ];*/

    /*public function sceneEdit()
    {
        return $this->only(['name','age'])
            ->append('name', 'min:5')
            ->remove('age', 'between')
            ->append('age', 'require|max:100');
    }*/



}
