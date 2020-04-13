<?php
/*
 * 登录、验证 控制器
 * */
namespace app\admin\controller;

use gmars\rbac\Rbac;
use think\captcha\Captcha;
use think\Controller;
use think\Db;
use app\admin\model\User;
use app\admin\model\Log;
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

        $config=Db::name('config')->cache(true,60)->column('value','varname');
        $this->assign('config',$config);


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

            $user=User::where('mobile',$name)->find();
            if(!$user){
                throw new Exception('未注册的手机号');
            }
            if(md5($password)!=$user['password']){
                throw new Exception('账号、密码不匹配');
            }
            if($user['status']==-1){
                throw new Exception('抱歉您的账户已被冻结，无法登录');
            }
            //每次登录重新生成sessionId
            Session::regenerate();
            //保存用户登录信息
            $data=[
                'uid'=>$user['id'],
                'nickname'=>$user['nickname'],
                'mobile'=>$user['mobile'],
                'last_login_time'=>$user['last_login_time'],
                'status'=>$user['status'],
            ];
            session('user',$data);
            $nt=time();
            //保存登录状态
            session('loginStatus',1);
            //更新用户最后一次登录时间及登录sessionID
            $sdata=[
                'last_login_time'=>$nt,
                'ip'=>Request::ip(),
                'session_id'=>session_id()
            ];
            $user->save($sdata);
            //获取权限
            if($user['status'] < 99){
                $rbacObj = new Rbac();
                $rbacObj->cachePermission($user['id']);
            }
            //返回登录成功
            $err['code']=0;
            //添加日志
            $this->addLog(4,'登录系统');
            //记录登录信息
            $login_data=[
                'uid'=>$user['id'],
                'ip'=>Request::ip(),
                'create_time'=>$nt,
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
        Session::clear('manage');
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
