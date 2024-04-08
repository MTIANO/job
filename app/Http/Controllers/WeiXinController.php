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
        /*$msg = json_decode('{"ToUserName":"gh_03aa44ccfbb4","FromUserName":"oERWv6qbxUaXC6Thly0ggeAkVilM","CreateTime":"1712571528","MsgType":"video","MediaId":"_w6KyBYugt6uOItB6sJmrqwXSSw30C4WP-Kplnuphr8AaESXLT4VbP5an9CA4ur10RDGzT3B5NCnL21uPhe-gg","ThumbMediaId":"_w6KyBYugt6uOItB6sJmriB476gBWZl8F1AVB34osXR6KGimlu49LOnfKYbYD7Rm","MsgId":"24518125813309221","Encrypt":"TXgGzSTHtQhvoxtzrjeWVvs\/oE8RI8ooLa4owM0iBF16icWPjuwDpwoNsvgq2g8gJRLDxWGHqMTETC6AABjtGvcXZPWWHGf3JZAcjGe41IqnwPM1uKwjutPRkiKa6+KydiF80zwcF\/hxSPWthqm2xeF\/2RS3scslq3KtBls3RxIdmmAqa\/Zr6LDl\/gl\/8b8tWLW7eh5fN4nh1nxM\/DUbI9STMWEWt4+KLbnRjCLQjp1vpAN+uMoCcRYtXiWz+rFpY\/70vldyo5wtE\/BOh3ee2X9D04pP8Pa4iUGTcw4KPhzwjMzwEGw+ucRAGlZaFVVLHQZY+jRTRggTfB9i8vwvdI7YI3cMAuF0\/j6p\/R9HxSsFDx8rYQY2jOfJMxaT3VPBZzTvDIKZpSSNvC\/et2kvLHkyxUq4mDlUh7CUyj96sZtjB4vvHqpFIKAKVF94G9RKPmE5yQO+EuWArjs299L49X02Cwekzt535rstp5TSik\/A1vHaFzh153GXe8iSX25C8VULmgGHX9naGM66oRbqaYuDBVaRjOehEVOSPAb5m56LKIqN0+Hx6FtgK7n0JW9ZDiodeCqDsIHuIZdR8zGG5ifhb8H8YJLsbL6OqD\/eCaxUGXVC2LZDXY\/DuqGka2942rfgUk3ndcYWwJgMVHAHthclRdDW9b\/1LQE82jeaWvQ="}',true);*/

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
                case'video':
                    $text = $CommonService->save_video($msg);
                    return $CommonService->doText($msg,'上传成功');
                    break;
            }
        }catch (Exception $e) {
            Log::channel('daily')->error(json_encode($msg,JSON_UNESCAPED_UNICODE).'==='.$e->getMessage());
            return $CommonService->doText($msg,'网络错误，请稍后重试!--'.$e->getMessage());
        }

    }

}
