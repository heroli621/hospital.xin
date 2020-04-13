<?php
/*
 * 评价 验证器
 * */
namespace app\shop\validate;

use think\Validate;

class EvaluateValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
        'goods_name'  => 'require|max:255',
        'cover'  => 'require',
        'content'  => 'require',
    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [
        'goods_name.require' => '商品名称必须填写',
        'goods_name.max'     => '商品名称最多不能超过255个字符',
        'cover'  => '商品图片至少上传一张',
        'content'     => '商品详情必须填写',
    ];









}
