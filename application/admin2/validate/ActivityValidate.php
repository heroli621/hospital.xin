<?php
/*
 * 优惠活动 验证器
 * */
namespace app\admin2\validate;

use think\Validate;

class ActivityValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
        'activity_title'  => 'require|max:255',
        'sale'  => 'require|number',
        'start_stop'  => 'require',
        'goods_id'  => 'require',
    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [
        'activity_title.require' => '标题必须填写',
        'activity_title.max'     => '标题最多不能超过255个字符',
        'sale.require'     => '优惠必选填写',
        'sale.number'     => '优惠只能填写数字',
        'require'  => '起止日期必须选择',
        'content'     => '活动详情必须填写',
        'goods_id'     => '商品参数错误',
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
