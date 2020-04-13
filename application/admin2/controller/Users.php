<?php

/*
 * 管理员控制器
 * */
namespace app\admin2\controller;


use app\model\admin2;
use gmars\rbac\Rbac;
use think\Db;
use think\Exception;
use think\facade\Request;



class Users extends BaseUser
{
    //中间件
    protected $middleware = [
        'app\admin2\middleware\Auth' => ['except' => ['personal','saveEdit','synchronization'] ],
    ];

    //管理员列表
    public function index()
    {
        $m=new \app\model\Admin();
        $data=$m->with('role')->where('id','<>',1)->order('last_login_time desc,update_time desc')->paginate(10);
        //dump($data[0]);exit;
        $this->assign('data',$data);

        return view('index');
    }

    //添加管理员 （本项目弃用）
    /*public function add()
    {
        //获取角色数据
        $roles=Db::name('role')->field('id,name')->where('status',1)->select();
        $this->assign('roles',$roles);
        return view('add');
    }*/

    //分配权限角色
    public function detail()
    {
        //获取角色数据
        $roles=Db::name('role')->field('id,name')->where('status',1)->select();
        $this->assign('roles',$roles);
        //获取管理员资料
        $id=input('id');

        $this->assign('id',$id);
        //获取员工角色数据
        $user_role=Db::name('user_role')->where('user_id',$id)->column('role_id');
        $this->assign('user_role',$user_role);
        return view('detail');
    }

    //删除管理员
    public function del(){

        $id=input('id',0);
        try{
            if($id==1 || $id==2){
                throw new Exception('超管账号无法删除');
            }
            $info=Db::name('user')->find($id);
            if(!$info){
                throw new Exception('未知的参数');
            }
            $rbacObj=new Rbac();
            $re=$rbacObj->delUser($id);
            if(!$re){
                throw new Exception('网络延迟，稍后重试');
            }
            //更新员工表
            if($info['member_id']){
                Db::name('staff')->where('member_id',$info['member_id'])->setField('status',0);
            }
            $this->err['code']=0;
            //添加日志
            $this->addLog(2,'删除管理员【'.$info['nickname'].'】');
        }catch(Exception $e){
            $this->err['msg']=$e->getMessage();
        }
        return json($this->err);
    }

    //后台管理员添加提交 （本项目弃用）
    /*public function saveAdd()
    {
        $data=input('post.');
        Db::startTrans();
        try{
            $validate=new Admin();
            if(!$validate->check($data)) {
                throw new Exception($validate->getError());
            }
            $add_data = [
                'mobile' => $data['phone'],
                'status' => 0,
                'password' => md5($data['psd']),
                'nickname'=>$data['username'],
                'email'=>$data['mail'],
                'des'=>$data['des'],
                'create_time'=>time()
            ];
            $rbacObj=new Rbac();
            //添加管理员
            $id=Db::name('user')->insertGetId($add_data);
            if(!$id){
                throw new Exception('网络延迟，稍后再试');
            }
            //处理角色绑定
            $roles=$data['roles'];

            //给管理员绑定角色
            if(!empty($roles)){
                //使用RBAC插件assignUserRole()方法进行用户与角色绑定操作
                $re=$rbacObj->assignUserRole($id,$roles);
                if(!$re){
                    throw new Exception('角色分配发生未知错误');
                }
            }
            //提交事务
            Db::commit();
            $this->err['code']=0;
            //添加日志
            $this->addLog(1,'添加管理员信息【'.$data['username'].'】');

        }catch (Exception $e){
            $this->err['msg']=$e->getMessage();
            Db::rollback();//事务回滚
        }
        //返回处理结果
        return json($this->err);

    }*/

    //分配角色保存
    public function saveEdit()
    {
        $data=input('post.');
        //dump($data);exit;
        // 启动事务
        Db::startTrans();
        try{
            //处理角色绑定
            $roles=$data['roles'];
            //获取员工角色数据
            $del_role=[];//取消绑定的角色关系
            $user_role=Db::name('user_role')->where('user_id',$data['id'])->select();
            foreach($user_role as $k=>$val){
                //原绑定角色与新绑定角色比对，相同的不再添加，没有的取消绑定
                $index=array_search($val['role_id'],$roles);
                if($index){
                    //已存在的不再重复添加
                    unset($roles[$index]);
                }else{
                    //不存在的取消绑定
                    $del_role[]=$val['id'];
                }
            }
            //取消对应角色绑定
            if(!empty($del_role)){
                $re=Db::name('user_role')->delete($del_role);
                if(!$re){
                    throw new Exception('绑定管理员角色信息失败');
                }
            }
            //绑定新的角色
            if(!empty($roles)){
                $rbacObj = new Rbac();
                //使用RBAC插件assignUserRole()方法进行用户与角色绑定操作
                $re=$rbacObj->assignUserRole($data['id'],$roles);
                if(!$re){
                    throw new Exception('角色分配发生未知错误');
                }
            }
            //提交事务
            Db::commit();
            $this->err['code']=0;
            //添加日志
            $this->addLog(3,'修改管理员信息【'.$data['username'].'】');

        }catch (Exception $e){
            $this->err['msg']=$e->getMessage();
            Db::rollback();//事务回滚
        }
        //返回处理结果
        return json($this->err);
    }

    //ajax修改管理员状态
    public function changeStatus()
    {
        $id=input('post.id');

        $info=Db::name('user')->find($id);
        if(!$info){
            $this->err['msg']='未知参数';
            return json($this->err);
        }
        $status=input('post.status',0,'int');
        if($info['status']==$status){
            return json($this->err);
        }
        $row=Db::name('user')->update(['status'=>$status,'id'=>$id,'update_time'=>time()]);
        if($row){
            $this->err['code']=0;
            $this->err['data']=$status;
            //添加日志
            $this->addLog(3,'修改管理员状态【'.$info['nickname'].'】');
        }else{
            $this->err['msg']='网络延迟，稍后再试';
        }

        return json($this->err);
    }

    //重置密码
    public function resetPassword(){
        if(Request::isAjax()){
            $id=input('post.id',session('user.uid'));
            $info=Admin::find($id);
            try{
                if(!$info){
                    throw new Exception('未知参数');
                }
                //验证
                $password=input('post.password');

                if(!preg_match('/^[a-zA-Z0-9_]{6,16}$/',$password)) {
                    throw new Exception('密码格式错误');
                }
                $old_password=input('post.old_pass',false);
                if($old_password && md5($old_password)!==$info->password){
                    //存在原密码则验证
                    throw new Exception('原密码输入错误');
                }
                $re_password=input('post.re_password',0);
                if($re_password && $re_password!==$password){
                    //存在重复密码则验证
                    throw new Exception('两次输入的密码不一致');
                }
                //重置密码并提下线
                $row=$info->save(['password'=>$password,'session_id'=>'resetPassword']);
                if(!$row){
                    throw new Exception('网络延迟，稍后再试');
                }

                $this->err['code']=0;
                $this->err['msg']='密码重置成功';
                //添加日志
                $this->addLog(3,'重置管理员【'.$info['nickname'].'】密码');

            }catch (Exception $e){
                $this->err['msg']=$e->getMessage();
            }
        }
        return json($this->err);
    }

    /*************************个人中心*********************************/
    //重置密码
    public function personal(){
        $member=Db::name('user')->where('id',session('user.uid'))->value('member_id');
        $this->assign('member',$member);

        return view('personal');
    }

    //同步手机、姓名
    public function synchronization(){
        if(Request::isAjax()){
            $syn=input('syn',0);
            try{
                if(!$syn){
                    throw new Exception('参数无效');
                }
                //原始数据
                $info=Admin::find(session('user.uid'));
                if(!$info || !$info['member_id']){
                    throw new Exception('您不是员工组提升的管理员，无法同步数据');
                }
                $old_data=[
                    'mobile'=>$info['mobile'],
                    'nickname'=>$info['nickname'],
                ];
                //新数据
                $new_data=Db::name('member')
                    ->field('phone mobile,realname nickname')
                    ->where('id',$info['member_id'])
                    ->find();
                if(!$new_data){
                    throw new Exception('会员参数无效');
                }
                if($old_data['mobile']==$new_data['mobile'] && $old_data['nickname']==$new_data['nickname']){
                    throw new Exception('数据未发生变化，无需同步');
                }
                //同步数据
                if(!$info->save($new_data)){
                    throw new Exception('网络错误，稍后重试');
                }
                $this->err['data']=0;
                if($old_data['mobile']!=$new_data['mobile']){
                    $this->err['data']=1;
                    session(null);
                }
                $this->err['code']=0;
            }catch (Exception $e){
                $this->err['msg']=$e->getMessage();
            }
        }
        return json($this->err);
    }

    //保存修改 （本项目弃用）
   /* public function saveUser(){
        $data=input('post.');
        $psd=$data['psd'];
        //dump($data);exit;
        try{
            $save_data = [
                'id'=>$data['id'],
                'nickname'=>$data['username'],
                'email'=>$data['mail'],
                'des'=>$data['des'],
                'update_time'=>time()
            ];
            $validate=new Admin();
            if(empty($psd)){
                if(!$validate->scene('edit')->check($data)) {
                    throw new Exception($validate->getError());
                }
            }else{
                if(!$validate->scene('save')->check($data)) {
                    throw new Exception($validate->getError());
                }
                $save_data['password']=md5($psd);
            }


            //修改用户信息
            $up=Db::name('user')->update($save_data);
            if(!$up){
                throw new Exception('修改管理员信息失败');
            }

            $this->err['code']=0;
            session('user.nickname',$data['username']);
            //添加日志
            $this->addLog(3,'修改个人资料【'.$data['username'].'】');

        }catch (Exception $e){
            $this->err['msg']=$e->getMessage();
        }
        //返回处理结果
        return json($this->err);
    }*/

    public function _empty()
    {
        //把所有城市的操作解析到city方法
        return view('public/404');
    }




}
