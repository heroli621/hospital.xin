<?php
/*
 * 讲师列表 验证器
 * */
namespace app\admin\validate;

use think\Validate;

class ColumnValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
        'name'  => 'require|max:32',
        'link'  => 'require',
    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [
        'name.require' => '导航名称必须填写',
        'name.max'     => '导航名称最多不能超过32个字符',
        'link'     => '链接地址必须填写',
    ];


}
