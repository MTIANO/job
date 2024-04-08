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
        //$msg = $CommonService->getMsg();
        $msg = json_decode('{"ToUserName":"gh_03aa44ccfbb4","FromUserName":"oERWv6qbxUaXC6Thly0ggeAkVilM","CreateTime":"1712555941","MsgType":"image","PicUrl":"http:\/\/mmbiz.qpic.cn\/sz_mmbiz_jpg\/WeBRibejeeuPypGO2jS8jsysb4Dn084hlYicnKxK3cCw3XWI7X5YTiaibEMLssnmBM18Dlj59GtYokTn8awOJ8vAeA\/0","MsgId":"24517906352054932","MediaId":"ZKRPipIEk_tpiWmeYvtD4kB9UV-CwsVEN-0eB11B4soY8DWvOAh4AILvjTNt4zzl","Encrypt":"zg0NWaxg59qmnc976ZOeVa7wULeN2CblSqWWXS\/XU+rX2KHziaDod\/w3cLGySxB10c4vPUeTlB2GyAFJxA53BE8bSFiEbZn7RA3BbqH4mCugTLCteIxitU86gX0ZIK9hUmmMm1LoTrEEYvSzDxc37hcbDCfXrOUZVYdykmx\/sEYqintDMM7u9U\/dFnntWBNmwzsmO+DZf7Ghu70XYKC670ay8R\/sK2UvddnSpRVUfAJH\/dbpSqO5Rmr9dadKh+4mJ4Rpoyn+I3TvvVqv56T9RFsAe9H0Wu4RZetiQOflL6gFnj2rgWTdnqbn+73mGSw6k+Xj15SEV0MuEhZ9j2WEXkFSe3ILfDXG\/b22hJnMC34pihm25bepmGtYBLMnCvaM0Ypp+kKDzFNKoqznTXXQ5hS73DvW4uYtdMTEy3YkXEt1dkUdxjDm+5tsFeHCP\/q\/djgzBQeQu08uP6dR2OjnVeCegVjM6pbNriQFU8GOkU+ZH4NHcHOcpqRIw\/lHU8yuoPfE\/yuvHZSQwILUlFF7XSY\/1VmHqL+JvFKNCnrz0KamWUJn5JtJYf0d6DZxRjUrEXDFsBT64sKuvWFOvcUCVsejawYRxIXoorT0Wtawmo46iCF40diauHPdy1EFStQKZa+epokCNekLRZVziovQq26MgN2fSK5TH59jJKA+EKzB8h7elsN7ZEuR6MmfGP9dCM\/khp0cZePkoGB4ePn6dA=="}',true);

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
