<?php
/*
 * RBAC权限 控制器
 * */
namespace app\admin2\controller;

use app\admin2\validate\Permission;
use app\admin2\validate\RoleValidate;
use app\model\Role;
use gmars\rbac\Rbac;
use think\Db;
use think\Exception;


class Power extends BaseUser
{
    //中间件
    protected $middleware = [
        'app\admin2\middleware\Auth'=> [
            //无须权限验证
            'except' => ['_empty','saveRole']
        ],
    ];

    /*****************************权限管理*******************************/
    //权限列表
    public function index()
    {

        $data=Db::name('permission')->order('create_time desc')->paginate(10);
        //dump($data[0]);exit;
        $this->assign('data',$data);

        return view('index');
    }

    //添加权限
    public function add()
    {
        return view('permission_edit');
    }

    //编辑权限
    public function detail()
    {
        $id=input('id');
        //获取权限数据
        $info=Db::name('permission')->find($id);
        $this->assign('info',$info);
        return view('permission_edit');
    }

    //删除权限
    public function del(){

        $id=input('id');
        $info=Db::name('permission')->find($id);
        if(!$info){
            $this->err['msg']='未知的参数';
        }
        $rbacObj = new Rbac();
        $re=$rbacObj->delPermission($id);
        if($re){
            $this->err['code']=0;
            //添加日志
            $this->addLog(2,'删除权限【'.$info['name'].'】');
        }else{
            $this->err['msg']='网络延迟，稍后重试';
        }

        return json($this->err);
    }

    //权限添加、修改
    public function saveEdit()
    {
        $data=input('post.');
        //dump($data);exit;
        // 启动事务
        Db::startTrans();
        try{
            $validate=new Permission();
            if(!$validate->check($data)) {
                throw new Exception($validate->getError());
            }
            $save_data = [
                'name'=>$data['name'],
                'path'=>$data['path'],
                'description'=>$data['des'],
                'create_time'=>time()
            ];
            $rbacObj = new Rbac();
            if(!empty($data['id'])){
                //修改
                $type=3;
                $save_data['id']=$data['id'];
                unset($data['create_time']);
                $up=$rbacObj->editPermission($save_data);
            }else{
                $type=1;
                $up=$rbacObj->createPermission($save_data);
            }
            if(!$up){
                throw new Exception('保存权限失败');
            }
            //提交事务
            Db::commit();
            $this->err['code']=0;
            $this->err['msg']=$type==1?'添加权限成功！':'修改权限成功！';
            //添加日志
            $this->addLog($type,'保存权限【'.$data['name'].'】');

        }catch (Exception $e){
            $this->err['msg']=$e->getMessage();
            Db::rollback();//事务回滚
        }
        //返回处理结果
        return json($this->err);
    }

    //ajax修改权限状态
    public function changeStatus()
    {
        $id=input('post.id');

        $info=Db::name('permission')->find($id);
        if(!$info){
            $this->err['msg']='未知参数';
            return json($this->err);
        }
        $status=$info['status']==1?0:1;
        $row=Db::name('permission')->update(['status'=>$status,'id'=>$id]);
        if($row){
            $this->err['code']=0;
            $this->err['data']=$status;
            //添加日志
            $this->addLog(3,'修改权限状态【'.$info['name'].'】');
        }else{
            $this->err['msg']='网络延迟，稍后再试';
        }

        return json($this->err);
    }

/***********************************角色管理***************************************/
    //角色列表
    public function roleList(){
        $data=Role::with('permission')->order('sort_num desc')->paginate(10);
        $this->assign('data',$data);
        return view('role_list');
    }
    //编辑角色
    public function roleEdit()
    {
        $id=input('id',0);
        $permission_ids=[];
        if($id){
            //获取角色数据
            $info=Db::name('role')->find($id);
            $this->assign('info',$info);
            //获取绑定权限id组
            $permission_ids=Db::name('role_permission')
                ->where('role_id',$id)
                ->column('permission_id');
        }
        //获取权限数据
        $permissions=Db::name('permission')
            ->where('status',1)
            ->order('path')
            ->select();
        $this->assign('permissions',$permissions);
        $this->assign('p_ids',$permission_ids);

        return view('role_edit');
    }
    //删除角色
    public function roleDel(){

        $id=input('post.id');

        $info=Db::name('role')->find($id);
        if(!$info){
            $this->err['msg']='未知的参数';
        }

        $re=Db::name('role')->delete($id);
        if($re){
            Db::name('role_permission')->where('role_id',$id)->delete();
            $this->err['code']=0;
            //添加日志
            $this->addLog(2,'删除角色【'.$info['name'].'】');
        }else{
            $this->err['msg']='网络延迟，稍后重试';
        }

        return json($this->err);
    }
    //保存角色
    public function saveRole()
    {
        $data=input('post.');
        Db::startTrans();
        try{
            $validate=new RoleValidate();
            if(!$validate->check($data)) {
                throw new Exception($validate->getError());
            }
            $save_data = [
                'name' => $data['name'],
                'description' => $data['des'],
                'sort_num' => $data['sort']
            ];
            $rbacObj=new Rbac();
            $type=1;
            if(!empty($data['id'])){
                $id=$data['id'];
                $save_data['id']=$id;
                $type=3;
                if(!$rbacObj->editRole($save_data)){
                    throw new Exception('修改保存失败！');
                }
            }else{
                //添加角色
                $id=Db::name('role')->insertGetId($save_data);
                if(!$id){
                    throw new Exception('网络延迟，稍后再试');
                }
            }

            /******************处理角色绑定*******************/
            //获取权限
            $permissions=$data['permissions'];
            //去除已绑定权限
            Db::name('role_permission')->where('role_id',$id)->delete();
            //给角色绑定新权限
            if(!empty($permissions)){
                //使用RBAC插件assignUserRole()方法进行用户与角色绑定操作
                $re=$rbacObj->assignRolePermission($id,$permissions);
                if(!$re){
                    throw new Exception('角色分配发生未知错误');
                }
            }else{
                Db::name('role_permission')->where('role_id',$id)->delete();
            }
            //提交事务
            Db::commit();
            $this->err['code']=0;
            $this->err['msg']='保存角色成功！';
            $msg=$type==1?'添加角色':'修改角色';
            //添加日志
            $this->addLog($type,$msg.'【'.$data['name'].'】');

        }catch (Exception $e){
            $this->err['msg']=$e->getMessage();
            Db::rollback();//事务回滚
        }
        //返回处理结果
        return json($this->err);

    }


    public function _empty()
    {
        //把所有城市的操作解析到city方法
        return view('public/404');
    }
}
