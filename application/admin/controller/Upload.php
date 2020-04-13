<?php
/*
 * neditor编辑器上传处理 控制器
 *
 * */
namespace app\admin\controller;


use app\admin\model\CoursewareDownload;
use think\Db;
use think\facade\Request;
use think\facade\Validate;
use think\response\Download;

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
        //大小限制 2MB
        $size=2*1024*1024;
        //类型限制
        $ext='jpg,jpeg,png,gif,bmp,ico';
        // 移动到框架网站根目录/uploads/ 目录下
        $info = $file->validate(['size'=>$size,'ext'=>$ext])->move( './uploads/neditor/images');
        if($info){
            // 成功上传后 获取上传信息
            // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
            //dump($info);
            $pname=str_replace('./','/',$info->getPathName());
            $this->err['code']=200;
            $url=str_replace('\\','/',$pname);
            $this->err['url']=$url;

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
        //大小限制 20MB
        $size=20*1024*1024;
        //类型限制
        $ext=["mp4", "webm", "flv", "ogg", "f4v"];
        // 移动到框架网站根目录/uploads/ 目录下
        $info = $file->validate(['size'=>$size,'ext'=>$ext])->move( './uploads/neditor/videos');
        if($info){
            // 成功上传后 获取上传信息
            //dump($info);
            $pname=str_replace('./','/',$info->getPathName());
            $this->err['code']=200;
            $url=str_replace('\\','/',$pname);
            $this->err['url']=$url;

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
        //大小限制 20MB
        $size=20*1024*1024;
        //类型限制
        $ext=["mp4", "webm", "flv", "ogg", "f4v","txt","doc","docs","xls","xlsx","ppt","pdf","odt","ott","fodt","uot","xml","dot","htm","html","rtf","docm","zip","rar","tar","7z","tar.gz","tar.bz","tar.xz"];
        // 移动到框架网站根目录/uploads/ 目录下
        $info = $file->validate(['size'=>$size,'ext'=>$ext])->move( './uploads/neditor/files');
        if($info){
            // 成功上传后 获取上传信息
            //dump($info);
            $pname=str_replace('./','/',$info->getPathName());
            $this->err['code']=200;
            $url=str_replace('\\','/',$pname);
            $this->err['url']=$url;

        }else{
            // 上传失败获取错误信息
            $this->err['message']=$file->getError();
        }
        return json($this->err);
    }

    //下载
    public function down($file_name='',$file_dir=''){
        $file_name=input('file_name',$file_name);
        $file_dir=input('file_dir',$file_dir);

        $download =  new Download($file_dir);
        return $download->name($file_name);
    }

    //课件下载
    public function coursewareDown(){
        $mid=input('mid',0);
        $cid=input('cid',0);
        if(!$mid || !$cid){
            return $this->_empty();
        }
        //获取课件信息
        $info=Db::name('courseware')->find($cid);
        if(!$info){
            return $this->_empty();
        }
        //验证记录是否存在
        $rule=[
            'courseware_id'=>'unique:courseware_download,courseware_id^member_id',
        ];
        $validate=Validate::make($rule);
        if($validate->check(['courseware_id'=>$cid,'member_id'=>$mid])){
            //添加课件下载记录
            $d=new CoursewareDownload();
            $d->courseware_id=$cid;
            $d->member_id=$mid;
            $d->course_id=$info['course_id'];
            $d->save();
        }
        return $this->down($info['file_name'],'.'.$info['url']);
    }

    public function _empty()
    {
        return view('public/404');
    }

}
