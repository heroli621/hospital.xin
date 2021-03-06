<?php
/*
 * 讲师管理 控制器
 *
 * */
namespace app\admin\controller;



use app\admin\validate\ColumnValidate;
use app\admin\model\Menus;
use app\admin\model\MenusModel;
use think\Db;
use think\Exception;
use think\facade\Request;


class Columns extends BaseUser
{
    //中间件
    protected $middleware = [
        'app\admin\middleware\Auth' => [
            //无须权限验证
            'except' => ['guide','uploadPic','_empty','saveEdit']
        ],
    ];
    protected $nav_index='admin/columns/index';
    protected $pid=3;
    protected $left_menus=[];
    public function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub
        $this->assign('nav_path',Db::name('menus')->where('id',$this->pid)->value('link'));
        $left_menus=$this->getNav($this->pid);
        $this->left_menus=$left_menus;
        $this->assign("left_menus",$left_menus);

    }

    public function guide(){
        if(count($this->left_menus)){
            $menu=array_shift($this->left_menus);
            return redirect($menu['link']);
        }else{
            return view('public/guide');
        }

    }
    /****************************列表*****************************/
    public function index()
    {
        if(Request::isAjax()){

            //获取数据
            $data=MenusModel::with('self')->order('path')->select();

            return json(['code'=>0,'msg'=>'','count'=>count($data),'data'=>$data]);
        }else{
            $this->assign('nav_title',$this->nav_index);
            return view('index');
        }

    }
    //编辑页
    public function edit(){

        $id=input('id',0);
        if($id){
            $info=Db::name('menus')->find($id);
            $this->assign('info',$info);
        }
        //父级
        $menus=Db::name('menus')
            ->field('id,name,path')
            ->where('type','<',2)
            ->order('path')
            ->select();
        $this->assign('menus',$menus);
        $this->assign('nav_title',$this->nav_index);
        return view('edit');
    }

    //编辑提交
    public function saveEdit()
    {
        $data=input('post.');
        $id=input('id',0);
        unset($data['id']);
        try{
            $validate=new ColumnValidate();
            if(!$validate->check($data)){
                throw new Exception($validate->getError());
            }
            $parent_path=Db::name('menus')->where('id',$data['parent_id'])->value('path');
            if($id){
                $info=MenusModel::find($id);
                if(!$info){
                    throw new Exception('参数错误');
                }
                $type=3;
                $msg='修改栏目';
                $data['path']=$parent_path?$parent_path.'-'.$id:'0-'.$id;
                $levels=count(explode('-',$data['path']));
                $data['type']=$levels==2?0:($levels==3?1:2);
                $res=$info->save($data);
                if(!$res){
                    throw new Exception('网络延迟，稍后再试');
                }

            }else{
                $type=1;
                $msg='添加栏目';
                $res=MenusModel::create($data);
                $res->path=$parent_path?$parent_path.'-'.$res['id']:'0-'.$res['id'];
                $levels=count(explode('-',$res->path));
                $res->type=$levels==2?0:($levels==3?1:2);
                $res->save();
                if(!$res){
                    throw new Exception('网络延迟，稍后再试');
                }
            }
            $this->err['code']=0;
            $this->err['msg']=$msg."【{$data['name']}】".'成功！';
            //添加日志
            $this->addLog($type,$msg."【{$data['name']}】");
        }catch (Exception $e){
            $this->err['msg']=$e->getMessage();
        }

        return json($this->err);
    }

    //ajax修改状态 开发账号专用
    public function changeSort()
    {
        $id=input('id');

        $info=Menus::find($id);
        if(!$info){
            $this->err['msg']='未知参数';
            return json($this->err);
        }
        $field=input('post.field','');
        $value=input('post.value/d',0);
        $info[$field]=$value;
        $row=$info->save();
        if($row){
            $this->err['code']=0;
            $this->err['data']=$value;
            //添加日志
            $this->addLog(3,'修改BANNER图【'.$info['name'].'】排序');
        }else{
            $this->err['msg']='网络延迟，稍后再试';
        }

        return json($this->err);
    }

    //ajax修改字段值
    public function changeField()
    {
        $id=input('id');

        $info=MenusModel::find($id);
        if(!$info){
            $this->err['msg']='未知参数';
            return json($this->err);
        }
        $field=input('post.field');
        $value=input('value');
        $info[$field]=$value;
        $row=$info->save();
        if($row){
            $this->err['code']=0;
            switch ($field){
                case "sort":
                    $str='排序';
                    break;
                case "name":
                    $str='名称';
                    break;
                case "link":
                    $str='链接地址';
                    break;

            }
            //添加日志
            $this->addLog(3,'修改栏目【'.$info['name'].'】'.$str);
        }else{
            $this->err['msg']='网络延迟，稍后再试';
        }

        return json($this->err);
    }

    //删除
    public function del(){

        $id=input('id');

        $info=MenusModel::get($id);
        if(!$info){
            $this->err['msg']='未知的参数';
            return json($this->err);
        }
        $name=$info->name;

        $re=$info->delete();
        if($re){
            $this->err['code']=0;

            //添加日志
            $this->addLog(2,'删除栏目【'.$name.'】');
        }else{
            $this->err['msg']='网络延迟，稍后重试';
        }

        return json($this->err);
    }


    /*****************************公共部分**************************/
    //上传图片
    public function uploadPic(){
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('images');
        if(!$file){
            $this->err['msg']='文件大小超出服务器限制';
            return json($this->err);
        }
        //大小限制
        $size=config('image_upload_size');
        //类型限制
        $ext=config('image_upload_extension');
        // 移动到框架网站根目录/uploads/ 目录下
        $info = $file->validate(['size'=>$size*1024*1024,'ext'=>$ext])->move( './uploads/teacher');
        if($info){
            // 成功上传后 获取上传信息
            // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
            //dump($info);
            $pname=str_replace('./','/',$info->getPathName());
            $this->err['code']=0;
            $this->err['data']=str_replace('\\','/',$pname);

        }else{
            // 上传失败获取错误信息
            $this->err['msg']=$file->getError();
        }
        return json($this->err);
    }


    public function _empty()
    {
        //把所有城市的操作解析到city方法
        return view('public/404');
    }

}
