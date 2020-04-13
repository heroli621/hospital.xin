<?php
/*
 * 快递100接口 控制器
 * */
namespace app\admin2\controller;


use app\model\Delivery;
use think\Exception;

class ExpressApi
{
    private $key;
    public function initialize($key){
        parent::initialize();
        $this->key=$key;
    }

    //订阅接口
    public function poll(Array $info){
        $post_data = [];
        $post_data["schema"] = 'json' ;
        $callbackurl=url('notify',[],true,true);

        //callbackurl请参考callback.php实现，key经常会变，请与快递100联系获取最新key
        $post_data["param"]='{"company":'.$info['com'].', "number":'.$info['express_no'].',';
        $post_data["param"].='"key":'.$this->key.',';
        $post_data["param"].='"parameters":{"callbackurl":'.$callbackurl.',"autoCom":1,"phone":'.$info['phone'].'}}';

        $url='http://www.kuaidi100.com/poll';

        $o="";
        foreach ($post_data as $k=>$v)
        {
            $o.= "$k=".urlencode($v)."&";		//默认UTF-8编码格式
        }
        $data=substr($o,0,-1);
        $result=$this->curlPost($url,$data);
        return $result;
    }


    //通知回调
    public function notify(){
        $param=input('param');

        try{
            if(isset($param['lastResult']['nu']) ||  empty($param['lastResult']['nu'])){
                throw new Exception('error');
            }
            //$param包含了文档指定的信息，...这里保存您的快递信息,$param的格式与订阅时指定的格式一致
            $express_no=$param['lastResult']['nu'];
            $com=$param['comOld'];
            $m=Delivery::where('express_no',$express_no)->where('com',$com)->find();
            if(!$m){
                throw new Exception('error');
            }
            $m->status=$param['lastResult']['state'];
            $m->save();
            return  json(['result'=>true,'returnCode'=>200,'message'=>"成功"]);
            //要返回成功（格式与订阅时指定的格式一致），不返回成功就代表失败，没有这个30分钟以后会重推
        } catch(Exception $e)
        {
            return json(['result'=>false,'returnCode'=>500,'message'=>"失败"]);
            //保存失败，返回失败信息，30分钟以后会重推
        }

    }


    //curl post提交
    protected function curlPost($url,$data){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $result = curl_exec($ch);
        return $result;
    }



    public function _empty()
    {
        //把所有城市的操作解析到city方法
        return view('public/404');
    }

}
