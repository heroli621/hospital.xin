<?php
/*
 * 公共基础 控制器
 * */
namespace app\admin\controller;

use app\admin\model\Log;
use think\Controller;
use think\Db;
use think\facade\Request;


class BaseUser extends Controller
{
    //ajax返回值
    public $err=[
        'code'=>1,
        'msg'=>'SUCCESS',
    ];

    protected $uid;
    protected $user_state;

    public function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub
        //验证登录状态
        if(!session('?loginStatus') || !session('?user.uid')){
            if(Request::isAjax()){
                $this->err['msg']="抱歉您未登录，请刷新后重新登录";
                json($this->err)->send();exit;
            }else{
                redirect(url('/system'))->send();exit;
            }
        }
        $this->uid = session('user.uid');
        //实时获得用户状态
        $res=Db::name('user')
            ->field('session_id,status')
            ->where('is_delete','=',0)
            ->find($this->uid);
        if(!$res){//获取失败，账号被注销
            session(null);
            if(Request::isAjax()){
                $this->err['msg']="抱歉您的账户已被注销，若有问题可联系管理员";
                json($this->err)->send();exit;
            }else{
                view("Public/406")->send();exit;
            }
        }else{
            $this->user_state = $res['status'];
            $this->assign('user_state',$res['status']);
        }

        //判断账号是否冻结
        if($res['status']==-1){
            session(null);
            if(Request::isAjax()){
                $this->err['msg']="抱歉您的账户已被冻结，若有问题可联系管理员";
                json($this->err)->send();exit;
            }else{
                view("Public/405")->send();exit;
            }
        }

        //处理重置密码
        if($res['session_id']=='resetPassword'){
            session(null);
            if(Request::isAjax()){
                $this->err['msg']="抱歉您的密码已重置，请重新登录";
                json($this->err)->send();exit;
            }else{
                echo '<script src="/static/admin/js/jquery-3.1.1.min.js" type="text/javascript"></script>
                <script src="/static/admin/lib/layer/layer.js" type="text/javascript"></script>
                <script>
                $(function() {
                 
                var url="/system.html";
                layer.alert("抱歉您的密码已重置，请重新登录",function() {
                   if(window.top==window.self){
                       //不存在父页面
                    location.href=url;
                }else{
                    parent.location.href=url;
                }
                });
                 });
                </script>';
                exit;
            }
        }
        if(session_id()!=$res['session_id']){

            session(null);
            if(Request::isAjax()){
                $this->err['msg']="抱歉您的账户已在其他地点登录，若有问题可联系管理员";
                json($this->err)->send();exit;
            }else{
                echo '<script src="/static/admin/js/jquery-3.1.1.min.js" type="text/javascript"></script>
                    <script src="/static/admin/lib/layer/layer.js" type="text/javascript"></script>
                    <script>
                    $(function() {
                     
                    var url="/system.html";
                    layer.alert("抱歉您的账户已在其他地点登录，若有问题可联系管理员",function() {
                       if(window.top==window.self){
                           //不存在父页面
                        location.href=url;
                    }else{
                        parent.location.href=url;
                    }
                    });
                     });
                    </script>';
                exit;
            }
        }
        //渲染用户信息
        $users=session('user');
        //dump($users);exit;
        foreach($users as $key=>$val){
            $this->assign($key,$val);
        }

        //系统配置
        $config=Db::name('config')->where('type',1)->cache(true,600)->column('value','varname');
        $this->assign('config',$config);


        //获取导航详细
        if($this->user_state == 100){
            $main_nav=Db::name('menus')
                ->where('type',0)
                ->order('sort desc')
                ->cache(true,10)
                ->select();
        }elseif($this->user_state==99){
            $main_nav=Db::name('menus')
                ->where('type',0)
                ->where('status',1)
                ->cache(true,600)
                ->order('sort desc')
                ->select();
        }else{
            $main_nav=Db::name('menus')
                ->where('type',0)
                ->where('status',1)
                ->where('auth',1)
                ->cache(true,600)
                ->order('sort desc')
                ->select();
        }

        $this->assign('main_nav',$main_nav);
    }

    /*
     * 添加操作日志
     * type 0:请求或操作错误；1:增加；2:删除；3：修改；4：登录；5：退出
     *
     * */

    public function addLog($type,$msg,$status=1)
    {
        $data=[
            'type'=>$type,
            'msg'=>$msg,
            'status'=>$status,
        ];
        $log=new Log();
        $log->create($data);
    }

    /*
     * 获取站点配置
     * $var_name->配置变量
     * $default->未获取到配置值时，返回的默认值
     * */
    public function getSiteConfig($var_name,$default=null){
        if(empty($var_name)){
            return $default;
        }
        $value=Db::name('config')->where('varname',$var_name)->value('value');
        return $value?$value:$default;
    }

    //获取左侧菜单
    protected function getNav($pid){
        $w=[];
        $t = 6;
        if($this->user_state < 100){
            $w=[['status','=',1]];
            $t = 600;
        }
        $left_menus=Db::name('menus')
            ->where('parent_id',$pid)
            ->where($w)
            ->order('sort desc')
            ->cache(true,$t)
            ->select()
            ->toArray();
        foreach ($left_menus as $key=>$v){
            $child=Db::name('menus')
                ->where('parent_id',$v['id'])
                ->where($w)
                ->order('sort desc')
                ->cache(true,$t)
                ->select();
            if(!$child->isEmpty()){
                $left_menus[$key]['child']=$child->toArray();
            }else{
                if($this->user_state < 99 && !auth_can($v['link'])){
                    unset($left_menus[$key]);
                }
            }
        }
        return $left_menus;
    }

    //上传文件
    protected function uploadFiles($file,$path,$size,$ext){
        // 获取表单上传文件 例如上传了001.jpg
        $result = [
            'code' => 1,
            'msg' => "success",
            'data' => '',
        ];
        $file = request()->file($file);
        if(!$file){
            $result['msg']='文件大小超出服务器限制';
            return $result;
        }
        // 移动到框架网站根目录/uploads/ 目录下
        $info = $file->validate(['size'=>$size*1024*1024,'ext'=>$ext])->move( $path);
        if($info){
            // 成功上传后 获取上传信息
            // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
            //dump($info);
            $pname=str_replace('./','/',$info->getPathName());
            $result['code']=0;
            $result['data']=str_replace('\\','/',$pname);

        }else{
            // 上传失败获取错误信息
            $result['msg']=$file->getError();
        }
        return $result;
    }

}