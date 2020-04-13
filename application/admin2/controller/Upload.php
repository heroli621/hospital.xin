<?php
/*
 * neditor编辑器上传处理 控制器
 *
 * */
namespace app\admin2\controller;



use think\Db;

class Upload
{


    //上传图片
    public function image(){
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('file');
        if(!$file){
            $this->err['message']='文件大小超出限制';
            return json($this->err);
        }
        //大小限制
        $size=$this->getSiteConfig('image_upload_size');
        //类型限制
        $ext=$this->getSiteConfig('image_upload_extension');
        // 移动到框架网站根目录/uploads/ 目录下
        $info = $file->validate(['size'=>$size*1024*1024,'ext'=>$ext])->move( './uploads/neditor/images');
        if($info){
            // 成功上传后 获取上传信息
            // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
            //dump($info);
            $pname=str_replace('./','/',$info->getPathName());
            $this->err['code']=200;
            $this->err['url']=str_replace('\\','/',$pname);

        }else{
            // 上传失败获取错误信息
            $this->err['message']=$file->getError();
        }
        return json($this->err);
    }

    //上传视频
    public function video(){
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('file');
        if(!$file){
            $this->err['message']='文件大小超出限制';
            return json($this->err);
        }
        //大小限制
        $size=20;
        //类型限制
        $ext=["mp4", "webm", "flv", "ogg", "f4v"];
        // 移动到框架网站根目录/uploads/ 目录下
        $info = $file->validate(['size'=>$size*1024*1024,'ext'=>$ext])->move( './uploads/neditor/videos');
        if($info){
            // 成功上传后 获取上传信息
            //dump($info);
            $pname=str_replace('./','/',$info->getPathName());
            $this->err['code']=200;
            $this->err['url']=str_replace('\\','/',$pname);

        }else{
            // 上传失败获取错误信息
            $this->err['message']=$file->getError();
        }
        return json($this->err);
    }
    //上传附件
    public function file(){
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('file');
        if(!$file){
            $this->err['message']='文件大小超出限制';
            return json($this->err);
        }
        //大小限制
        $size=20;
        //类型限制
        $ext=["mp4", "webm", "flv", "ogg", "f4v","txt","doc","docs","xls","xlsx","ppt","pdf","odt","ott","fodt","uot","xml","dot","htm","html","rtf","docm","zip","rar","tar","7z","tar.gz","tar.bz","tar.xz"];
        // 移动到框架网站根目录/uploads/ 目录下
        $info = $file->validate(['size'=>$size*1024*1024,'ext'=>$ext])->move( './uploads/neditor/files');
        if($info){
            // 成功上传后 获取上传信息
            //dump($info);
            $pname=str_replace('./','/',$info->getPathName());
            $this->err['code']=200;
            $this->err['url']=str_replace('\\','/',$pname);

        }else{
            // 上传失败获取错误信息
            $this->err['message']=$file->getError();
        }
        return json($this->err);
    }

    //获取指定网站配置
    public function getSiteConfig($var_name){
        if(empty($var_name)){
            return false;
        }
        return Db::name('config')->where('varname',$var_name)->value('value');
    }

    public function _empty()
    {
        //把所有城市的操作解析到city方法
        return view('public/404');
    }

}
