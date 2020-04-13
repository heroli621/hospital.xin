<?php
/*
 * 支付
 * */
namespace app\shop\controller;

use think\Controller;
use think\Db;
use think\Exception;


class Wechat extends Controller
{
    protected $appId='';
    protected $appSecret='';
    protected $template;


    public function wxServer(){
        $signature=input('signature/s','');
        $timestamp=input('timestamp/s','');
        $nonce=input('nonce/s','');
        $echostr=input('echostr/s','');
        $res=$echostr;
        try{
            if(!$signature || !$timestamp || !$nonce || !$echostr){
               throw new Exception('data error');
            }
            //$content='signature：'.$signature.PHP_EOL.'timestamp:'.$timestamp.PHP_EOL.'nonce:'.$nonce.PHP_EOL.'echostr:'.$echostr.PHP_EOL;

            //
            $token='bailiankaiwechartmp123';
            $list=[$token,$nonce,$timestamp];
            sort($list);
            $hashcode =sha1(implode($list));
            //file_put_contents('1.txt',$content.$hashcode,FILE_APPEND);
            if($hashcode!==$signature){
                throw new Exception('verify error');
            }
        }catch (Exception $e){
            //$echostr=$e->getMessage();
            //file_put_contents('1.txt',$echostr,FILE_APPEND);
            //接收消息
            $postObj = simplexml_load_string( file_get_contents("php://input") );
            if( strtolower( $postObj->MsgType) == 'event'){
                //如果是关注 subscribe 事件
                if( strtolower($postObj->Event == 'subscribe') ){
                    //回复用户消息(纯文本格式)
                    $toUser   = $postObj->FromUserName;
                    $fromUser = $postObj->ToUserName;
                    $nt     = time();
                    $msgType  =  'text';
                    $content  = '美丽热线：0551-62675687    18019948039 
魅力地址：合肥市政务区习友路保利香槟国际18栋3204室';
                    $template = "<xml>
                                <ToUserName><![CDATA[%s]]></ToUserName>
                                <FromUserName><![CDATA[%s]]></FromUserName>
                                <CreateTime>%s</CreateTime>
                                <MsgType><![CDATA[%s]]></MsgType>
                                <Content><![CDATA[%s]]></Content>
                                </xml>";
                    $res = sprintf($template, $toUser, $fromUser, $nt, $msgType, $content);
                    echo $res;
                    //注册用户
                    //验证是否注册
                    $member=Db::name('member')->where('openid',$toUser)->value('id');
                    if(!$member){
                        //注册
                        $access_token=$this->getAccessToken();
                        if(isset($access_token['access_token'])){
                            $wx_user=$this->getUserInfo($toUser,$access_token['access_token']);
                            if($wx_user){
                                //组装数据
                                $member_data=[
                                    'openid'=>$wx_user['openid'],
                                    'nickname'=>$wx_user['nickname'],
                                    'realname'=>$wx_user['nickname'],
                                    'phone'=>'',
                                    'address'=>$wx_user['province'].$wx_user['city'],
                                    'sex'=>$wx_user['sex'],
                                    'headimg'=>$wx_user['headimgurl'],
                                    'safe'=>substr(md5($wx_user['openid'].$nt),rand(0,25),6),
                                    'create_time'=>$nt
                                ];
                                //注册信息
                                $mid=Db::name('member')->insertGetId($member_data);
                                //分销关系
                                $res=Db::name('member_distribution')->where('member_id',$mid)->find();
                                if(!$res){
                                    Db::name('member_distribution')->insert(['member_id'=>$mid,'create_time'=>$nt]);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public function menu(){
        $access_token=$this->getAccessToken();

        if(isset($access_token['access_token'])){
            $api_url='https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$access_token['access_token'];
            $data='{
                "button":[
                    {
                        "name": "科技美容",
                        "sub_button": [
                            {
                                "type": "view",
                                "name": "核心科技",
                                "url": "'.url('/article/2',[],true,true).'"
                            },
                            {
                                "type": "view",
                                "name": "新闻中心",
                                "url": "'.url('/article/1',[],true,true).'"
                            },
                            {
                                "type": "view",
                                "name": "成功案例",
                                "url": "'.url('/article/3',[],true,true).'"
                            },
                            {
                                "type": "view",
                                "name": "最新优惠",
                                "url":"'. url('/activity',[],true,true).'"
                            }
                        ]
                    },
                    {
                        "name": "我要变美",
                        "sub_button": [
                            {
                                "type": "view",
                                "name": "预约美丽",
                                "url": "'.url('/subscribe',[],true,true).'"
                            },
                            {
                                "type": "view",
                                "name": "我要不老容颜",
                                "url": "'.url('/goods/index/1',[],true,true).'"
                            },
                            {
                                "type": "view",
                                "name": "我要身心解放",
                                "url": "'.url('/goods/index/2',[],true,true).'"
                            },
                            {
                                "type": "view",
                                "name": "我要香香的",
                                "url": "'.url('/goods/index/3',[],true,true).'"
                            },
                            {
                                "type": "view",
                                "name": "私人订制",
                                "url": "'.url('/goods/index/4',[],true,true).'"
                            }
                        ]
                    },
                    {
                        "name": "我的SPA",
                        "sub_button": [
                            {
                                "type": "view",
                                "name": "美丽商城",
                                "url":"'. url('/',[],true,true).'"
                            },
                            {
                                "type": "view",
                                "name": "会员中心",
                                "url": "'.url('/mine',[],true,true).'"
                            },
                            {
                                "type": "view",
                                "name": "积分商城",
                                "url": "'.url('Commodity/integral',[],true,true).'"
                            },
                            {
                                "type": "view",
                                "name": "分销体系",
                                "url": "'.url('Vip/fans',[],true,true).'"
                            }
                        ]
                    }
                ]
            }';
            $result = $this->curlPost($api_url, $data);
        }else{
            $result=$access_token['errmsg'];
        }
        return $result;
    }


    //获取access_token
    protected function getAccessToken(){
        $wx=Db::name('wechat')->find(1);
        $nt=time();
        if(!$wx['access_token'] || $wx['refresh_time'] < $nt){
            //获取access_token
            $url='https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$wx['app_id'].'&secret='.$wx['app_secret'];
            $redata=file_get_contents($url);
            $redata=json_decode($redata,true);
            if(isset($redata['access_token'])){
                $wx=[
                    'id'=>1,
                    'access_token'=>$redata['access_token'],
                    'expires_in'=>$redata['expires_in'],
                    'get_time'=>$nt,
                    'refresh_time'=>$nt+$redata['expires_in']
                ];
                Db::name('wechat')->update($wx);
            }else{
                return false;
            }
        }

        return $wx;
    }

    //curl post提交
    protected function curlPost($url,$data){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }
    //获取授权登录信息
    public function getUserInfo($openid,$access_token){
        $url='https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN';
        $res=json_decode(file_get_contents($url),true);
        if(!isset($res['errcode'])){
            return $res;
        }else{
            return false;
        }
    }

    /*
     * $openid 用户openid
     * $mid     微信模板表id
     * $arr     模板所需字段数据[顺序与模板参数顺序一致]
     * $url     模板跳转链接 默认不跳转
     * $miniprogram     跳小程序所需数据，默认不跳转。数组结构['appid'=>'','pagepath'=>'']
     * */
    public function setTemplate($mid,$arr,$url=null,$miniprogram=null){
        $template=Db::name('wechat_template')->find($mid);
        $data=vsprintf($template['template'], $arr);
        $url_str=$url?'"url":"'.$url.'",':'';
        $miniprogram_str=$miniprogram?'"miniprogram":{
            "appid":"'.$miniprogram['appid'].'",
             "pagepath":"'.$miniprogram['pagepath'].'"
           }':'';
        $this->template='{"touser":"%s",
        "template_id":"'.$template['template_id'].'",
        '.$url_str.$miniprogram_str.'
        "data":'.$data.'}';
    }

    //推送模板消息
    public function sendTemplate($openid){
        $access_token=$this->getAccessToken();
        //发送模板数据接口
        $url='https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$access_token['access_token'];
        $template=sprintf($this->template,$openid);
        return $this->curlPost($url,$template);
    }





}

?>