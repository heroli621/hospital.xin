<?php
/*
 * 网站配置 控制器
 * */
namespace app\admin2\controller;

use app\model\Log;
use think\Db;
use think\Exception;
use think\facade\Request;


class Site extends BaseUser
{
    //中间件
    protected $middleware = [
        'app\admin2\middleware\Auth' => [
            'except' => ['uploadPic','_empty','saveEdit']
        ],
    ];

    /*****************************网站配置管理*******************************/
    //系统配置列表
    public function system()
    {

        $data=Db::name('config')->where('type',1)->paginate(10);
        //dump($data[0]);exit;
        $this->assign('data',$data);
        $this->assign('type',1);

        return view('index');
    }

    //商城配置列表
    public function shop()
    {

        $data=Db::name('config')->where('type',2)->paginate(10);
        //dump($data[0]);exit;
        $this->assign('data',$data);
        $this->assign('type',2);

        return view('index');
    }

    //编辑配置
    public function edit()
    {
        $id=input('id',0);
        $type=input('type',1);
        if($id){
            //获取网站配置数据
            $info=Db::name('config')->find($id);
            $this->assign('info',$info);
            $type=$info['type'];
        }

        $this->assign('type',$type);

        return view('edit');
    }

    //配置添加、修改
    public function saveEdit()
    {
        $data=input('post.');
        //dump($data);exit;
        try{
            $save_data = [
                'name'=>$data['name'],
                'varname'=>$data['var_name'],
                'model'=>$data['model'],
                'value'=>$data['value'],
                'extension'=>$data['extension'],
                'type'=>$data['type'],
                'update_time'=>time()
            ];
            $validate=new \app\admin2\validate\Site();
            if(!empty($data['id'])){
                if(!$validate->scene('edit')->check($data)) {
                    throw new Exception($validate->getError());
                }
                //修改
                $type=3;
                $save_data['id']=$data['id'];
                unset($data['varname']);
                $up=Db::name('config')->update($save_data);
            }else{
                if(!$validate->check($data)) {
                    throw new Exception($validate->getError());
                }
                $type=1;
                $up=Db::name('config')->insertGetId($save_data);
            }
            if(!$up){
                throw new Exception('保存配置失败！');
            }

            $this->err['code']=0;
            $this->err['msg']=$type==1?'添加配置成功！':'修改配置成功！';
            //添加日志
            $this->addLog($type,'保存配置【'.$data['name'].'】');

        }catch (Exception $e){
            $this->err['msg']=$e->getMessage();
        }
        //返回处理结果
        return json($this->err);
    }



    /*******************************系统日志**********************************/
    //操作日志列表
    public function logs(){
        if(Request::isAjax()){
            $map=[];
            $listRow=input('limit');
            if(session('user.uid')!=1){
                $map[]=['uid','neq',1];
            }
            $data=Log::with('admin')
                ->where($map)
                ->order('create_time desc')
                ->paginate($listRow);

            return json(['code'=>0,'count'=>$data->total(),'data'=>$data->items()]);
        }else{
            return view('logs');
        }
    }


    //删除日志
    public function logDel(){
        $ids=input('ids');

        $re=Db::name('log')->delete($ids);
        if($re){
            $this->err['code']=0;
            $this->err['msg']='成功删除['.$re.']条系统日志！';
            //添加日志
            $this->addLog(2,'删除系统日志【'.$re.'】条');
        }else{
            $this->err['msg']='网络延迟，稍后重试';
        }

        return json($this->err);
    }

    /*******************************公共操作**********************************/
    //上传图片
    public function uploadPic(){
        //dump(request()->file());exit;
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('file');
        //大小限制
        $size=$this->getSiteConfig('image_upload_size');
        //类型限制
        $ext=$this->getSiteConfig('image_upload_extension');
        // 移动到框架网站根目录/uploads/ 目录下
        $info = $file->validate(['size'=>$size*1024*1024,'ext'=>$ext])->move( './uploads/site');
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
