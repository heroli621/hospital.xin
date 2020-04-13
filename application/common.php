<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件


//权限验证
function auth_can($path){
    $path=strtolower($path);
    $except=['/manage','admin/mine/info','admin/mine/repassword'];//例外路径
    if(in_array($path,$except)){
        return true;
    }
    $cacheName = Session::get('gmars_rbac_permission_name');
    if (empty($cacheName)) {
        return false;
    }
    $permissionList = cache($cacheName);
    if (empty($permissionList)) {
        return false;
    }

    if (isset($permissionList[$path]) && !empty($permissionList[$path])) {
        return true;
    }

    return false;
}

//子菜单显示缩进自定义
function diy_indent($path,$str='-',$h=2){
    $p_c=count(explode($str,$path))-$h;
    $re_str='&emsp;';
    return $p_c>0?str_repeat($re_str,$p_c):'';
}

//获取面包屑
function diy_crumbs($path,$str='-'){
    $p_a=explode($str,$path);
    $re_str='';
    foreach ($p_a as $v){
        if($v){
            $temp_str=db('menus')->where('id',$v)->value('name');
            $re_str.='<span>>'.$temp_str.'</span>';
        }
    }
    return $re_str;
}


/*
    * 发送短信接口
    *
    * accesskey    访问秘钥
    * secret       加密秘钥
    * mobile       手机号码
    * content      模板变量内容
    * templateId   模板id
    * sign         用户签名
    *
    * */
function sendSms($mobile,$content,$templateId='177365',$accesskey='Vvvga0cnVHQpEJOQ',$secret='2ckQwlnqj2FXbnbDdLcYgSPayaeblD41',$sign='【志愿填报系统】')
{
    $params='';//要post的数据
    //以下信息自己填以下
    $argv = array(
        'accesskey'=>$accesskey,     //访问秘钥
        'secret'=>$secret,     //秘钥。
        'content'=>$content,   //模板变量内容
        'mobile'=>$mobile,   //手机号码
        'templateId'=>$templateId,   //模板编号
        'sign'=>$sign   //用户签名。
    );
    foreach ($argv as $key=>$value) {
        $params.= $key."=".urlencode($value)."&";// urlencode($value);
    }
    $params=rtrim($params,'&');
    $url = "http://api.1cloudsp.com/api/v2/single_send?".$params; //提交的url地址
    $con= file_get_contents($url);  //获取信息发送后的状态
    return $con;
}


/*
 * 非常给力的encrypt_code加密函数,Discuz!经典代码
 *
 * $string：字符串，明文或密文；
 * $operation：DECODE表示解密，其它表示加密；
 * $key：密匙；
 * $expiry：密文有效期。
 *
 */
function encrypt_code($string, $operation = 'DECODE', $key = '', $expiry = 0) {
    // 动态密匙长度，相同的明文会生成不同密文就是依靠动态密匙
    $ckey_length = 4;

    // 密匙
    $key = md5($key ? $key : $GLOBALS['discuz_auth_key']);

    // 密匙a会参与加解密
    $keya = md5(substr($key, 0, 16));
    // 密匙b会用来做数据完整性验证
    $keyb = md5(substr($key, 16, 16));
    // 密匙c用于变化生成的密文
    $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';
    // 参与运算的密匙
    $cryptkey = $keya.md5($keya.$keyc);
    $key_length = strlen($cryptkey);
    // 明文，前10位用来保存时间戳，解密时验证数据有效性，10到26位用来保存$keyb(密匙b)，
    //解密时会通过这个密匙验证数据完整性
    // 如果是解码的话，会从第$ckey_length位开始，因为密文前$ckey_length位保存 动态密匙，以保证解密正确
    $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) :  sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
    $string_length = strlen($string);
    $result = '';
    $box = range(0, 255);
    $rndkey = array();
    // 产生密匙簿
    for($i = 0; $i <= 255; $i++) {
        $rndkey[$i] = ord($cryptkey[$i % $key_length]);
    }
    // 用固定的算法，打乱密匙簿，增加随机性，好像很复杂，实际上对并不会增加密文的强度
    for($j = $i = 0; $i < 256; $i++) {
        $j = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }
    // 核心加解密部分
    for($a = $j = $i = 0; $i < $string_length; $i++) {
        $a = ($a + 1) % 256;
        $j = ($j + $box[$a]) % 256;
        $tmp = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;
        // 从密匙簿得出密匙进行异或，再转成字符
        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }
    if($operation == 'DECODE') {
        // 验证数据有效性，请看未加密明文的格式
        if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) &&  substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
            return substr($result, 26);
        } else {
            return '';
        }
    } else {
        // 把动态密匙保存在密文里，这也是为什么同样的明文，生产不同密文后能解密的原因
        // 因为加密后的密文可能是一些特殊字符，复制过程可能会丢失，所以用base64编码
        return $keyc.str_replace('=', '', base64_encode($result));
    }
}
