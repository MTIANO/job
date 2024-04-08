<?php

namespace App\Http\Controllers;

use App\Models\MtUser;
use App\Services\CommonService;
use App\Services\WeiXinService;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Log;

class WeiXinController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;


    public function index(){
        $data = [
            'title'=>'会员的世界!',
            'ba'=>'备案号:粤ICP备16032172号-3',
        ];
        return view('welcome',$data);
    }

    /*public function test(){
        try {
        $open = (new OpenApiService())->completions('php 数组排序并分页');
            dump($open['choices'][0]['message']['content']);
            dump($open);
            die;
        }catch (Exception $e) {
            dump(123);
            dump($e->getMessage());die;
        }
    }*/

    public function firstValid(Request $request){
        if($request->method() === 'GET'){
            return (new CommonService())->checkSignature($_GET);
        }

        echo $this->responseMsg();
        exit();
    }

    public function responseMsg(){
        $CommonService = new CommonService();
        $msg = $CommonService->getMsg();
        /*$msg = json_decode('{"ToUserName":"gh_03aa44ccfbb4","FromUserName":"oERWv6qbxUaXC6Thly0ggeAkVilM","CreateTime":"1712470360","MsgType":"text","Content":"老黄历","MsgId":"24516680597512481","Encrypt":"dHsCKRH31ndPtqTnK0LgwkxJDei10Zv0hp9FjeYfhhaRronkRUj24O8FdAOY\/KiAKxV3rxzXSrizOrb2DQZ1gObj1TGd0v49o3Ya3XYWDraCXliY+9G3iFvWElWsDNddRzc+ije9p1D9ORE4UbKePW4gt41HNR0X19movQK1HvtDy69MQyNzIR06LaXfwuVk37MyhfSzyL3ni5minkZKFT5IvWDLV3nw53FbdkMpQG7+xOVuCt1cAjDeyahahLq9uxKmPcwwRgnBWGVcxl2VCvGniUTpHj2HjYY3+\/dDidTzczpMPzcdfXo8ZQ5b1SKMMuplQLYxp8OwaUvcIuKHKgOpdCSCqN5dQHVXLedU4CrxrMbbOJm9N7Ng2O0gM1Wr+J54S\/XJ0q5OsUpXIGCFnBSymMGuPULC76Nol25879o="}',true);*/

        if(!$msg){
            return false;
        }
        Log::channel('daily')->info(json_encode($msg,JSON_UNESCAPED_UNICODE));
        try {
            $CommonService->addUser($msg);
            $user = (new MtUser())->getUserByWinXinId($msg['FromUserName']);
            if(!$user){
                $CommonService->doText($msg,'获取用户失败!');
            }



            switch ($msg['MsgType']){
                case'event':
                    if($msg['Event'] === 'subscribe' ){
                        $text = '欢迎来到会员的世界!';
                        (new WeiXinService())->send('关注通知','新增一位关注者',date('Y-m-d H:i:s'));
                        return $CommonService->doText($msg,$text);
                    }
                    if($msg['Event'] === 'unsubscribe' ){
                        (new WeiXinService())->send('取消关注通知','失去一位关注者',date('Y-m-d H:i:s'));
                        $CommonService->disableUser($msg);
                        return true;
                    }
                    if($msg['Event'] === 'CLICK' ){
                        if($msg['EventKey'] === 'YS'){
                            return $CommonService->doText($msg,'遭受攻击，暂停服务');
                            //return $CommonService->doText($msg,(new YsService($user))->get_user());
                        }
                    }
                    break;
                case'text':
                    //OpenApiPush::dispatch(['user_info' => $msg,'text' => $msg['Content']]);
                    //return $CommonService->doText($msg,'回答生成中，请稍等！');
                    $text = $CommonService->manage($msg,$user);
                    if($text === false){
                        $text = '指令无效,更多功能指令请联系本人!(目前开放:老黄历, 图片, bog)';
                    }elseif($text === true){
                        $text = '操作成功!';
                    }

                    if(is_array($text)){
                        return $CommonService->doImg($msg,$text);
                    }

                    return $CommonService->doText($msg,$text);
                    break;
                case'image':
                    $text = $CommonService->save_img($msg);
                    return $CommonService->doText($msg,'上传成功');
                    break;
            }
        }catch (Exception $e) {
            Log::channel('daily')->error(json_encode($msg,JSON_UNESCAPED_UNICODE).'==='.$e->getMessage());
            return $CommonService->doText($msg,'网络错误，请稍后重试!');
        }

    }

}
