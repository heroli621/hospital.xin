<?php
/*
 * 登录、验证 控制器
 * */
namespace app\admin2\controller;

use gmars\rbac\Rbac;
use think\captcha\Captcha;
use think\Controller;
use think\Db;
use app\model\Admin;
use app\model\Log;
use think\Exception;
use think\facade\Request;
use think\facade\Session;

class Index extends Controller
{
    //登录界面
    public function index()
    {
        //验证登录状态
        if(session('?loginStatus')){
            return redirect(url('/manage'));
        }

        $config=Db::name('config')->column('value','varname');
        session('config',$config);
        foreach($config as $key=>$val){
            $this->assign($key,$val);
        }

        return view('login');
    }

    //输出验证码
    public function verify(){
        $config=[
            'fontSize'=>20,
            'useCurve'=>false,
            'length'=>4
        ];
        $captcha = new Captcha($config);
        return $captcha->entry();
    }

    //登录验证
    public function login(){
        $err=[
            'code'=>-1,
            'msg'=>'未知错误'
        ];
        $verify=input('verify');
        $captcha = new Captcha();
        try{
            if(!$captcha->check($verify)){
                throw new Exception('验证码错误或失效');
            }
            $name=input('name');
            $password=input('password');

            $m=new Admin();
            $data=$m->where('mobile',$name)->find();
            if(!$data){
                throw new Exception('未注册的手机号');
            }
            if(md5($password)!=$data['password']){
                throw new Exception('账号、密码不匹配');
            }
            if($data['status']==-1){
                throw new Exception('抱歉您的账户已被冻结，无法登录');
            }
            //每次登录重新生成sessionId
            Session::regenerate();
            //保存用户登录信息
            $user=[
                'uid'=>$data['id'],
                'nickname'=>$data['nickname'],
                'mobile'=>$data['mobile'],
                'last_login_time'=>$data['last_login_time'],
                'status'=>$data['status'],
            ];
            session('user',$user);
            //保存登录状态
            session('loginStatus',1);
            //更新用户最后一次登录时间及登录sessionID
            $sdata=[
                'last_login_time'=>time(),
                'ip'=>Request::ip(),
                'session_id'=>session_id()
            ];
            $m->save($sdata,['id'=>$data['id']]);
            //获取权限
            $rbacObj = new Rbac();
            $rbacObj->cachePermission($data['id']);
            //返回登录成功
            $err['code']=0;
            //添加日志
            $this->addLog(4,'登录系统');
            //记录登录信息
            $login_data=[
                'uid'=>$data['id'],
                'ip'=>$data['id'],
                'create_time'=>$data['id'],
            ];
            Db::name('login_log')->insert($login_data);
        }catch (Exception $e){
            $err['msg']=$e->getMessage();
        }
        return json($err);
    }

    //退出登录
    public function loginOut(){
        //添加日志
        $this->addLog(5,'退出登录');
        session(null);
        return json(['code'=>0]);
    }

    //添加操作日志
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

    public function _empty()
    {
        //把所有城市的操作解析到city方法
        return view('public/404');
    }

}
