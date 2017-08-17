<?php
/**
 * Created by PhpStorm.
 * User: love5
 * Date: 2017/8/17
 * Time: 18:29
 */

namespace Home\Controller;


use EasyWeChat\Foundation\Application;
use EasyWeChat\Message\News;
use EasyWeChat\Message\Text;
use Think\Controller;
require $_SERVER['DOCUMENT_ROOT']."/vendor/autoload.php";
class WechatController extends HomeController
{
    public function index(){
        //将配置文件放入到配置文件中
        $app = new Application(C('wechat_config'));
        $server = $app->server;
        //消息的处理
        $server->setMessageHandler(function ($message) {
            //消息的基本属性
            /**
             *  $message->ToUserName    接收方帐号（该公众号 ID）
            $message->FromUserName  发送方帐号（OpenID, 代表用户的唯一标识）
            $message->CreateTime    消息创建时间（时间戳）
            $message->MsgId         消息 ID（64位整型）
             */
            //文本消息


            switch ($message->MsgType) {
                case 'event':
                    //处理事件 关注和取消关注事件
                    switch ($message->Event){
                        case 'subscribe'://关注事件
                            return "欢迎关注我们的智能微信物业管理系统！";
                            break;
                        case 'unsubscribe':
                            //不处理
                            break;
                        case "CLICK":
                            //自定义菜单的点击事件
                            return $message->EventKey;
                            break;
                    }
                    break;
                case 'text':
                    //使用对象的方式处理文本消息
                    $content = $message->Content;
                    if($content){
                        preg_match("/^(\w)(.*)$/",$content,$matches);
                        switch ($matches[1]) {
                            case 's'://基于位置的搜索
                                $query = urlencode($matches[2]);//转义
                                //从数据库中查询出对应open_id的坐标
                                $user_location = M('location')->where(['open_id' => $message->FromUserName])->find();
                                $location = $user_location['x'] . ',' . $user_location['y'];
                                $search_url = "http://api.map.baidu.com/place/search?query={$query}&location={$location}&radius=1000&output=xml";
                                //解析xml
                                $simpleXml = simplexml_load_file($search_url);
//                dump($simpleXml);
                                $news = [];//所有的图文消息
                                $news_count = 0;
                                foreach ($simpleXml->results->result as $k => $v) {

                                    $url = html_entity_decode($v->detail_url);//将url中的实体符号转换回来
                                    $lng = (string)$v->location->lng;
                                    //file_put_contents('./debuglog.txt',implode(',',$message->FromUserName),FILE_APPEND);
                                    $lat = (string)$v->location->lat;
                                    //获取百度静态图片
                                    $image_url = "http://api.map.baidu.com/panorama/v2?ak=mzyIoPg42h4yy9Twcvcy9t0oWlvlTbhx&width=512&height=256&location={$lng},{$lat}&fov=180";
                                    $new = new News(['title' => (string)$v->name, 'description' => (string)$v->address, 'url' => $url, 'image' => $image_url]);
                                    $news[] = $new;
                                    $news_count++;
                                    if ($news_count >= 8) {
                                        break;
                                    }
                                }
                                //file_put_contents('./debuglog2.txt',222);exit;
                                return $news;
                                break;
                            case 'l'://搜索天气
                                $ajax = new curlAjax();
                                $str = urlencode($matches[2]);
                                //dump($str);exit;
                                $json = $ajax->httpGet('http://v.juhe.cn/weather/index?format=2&cityname=' . $str . '&key=290b7187396a3e88e3fc3a96a91f623e', '');
                                $arr = json_decode($json, true);
                                //dump($arr);exit;
                                if ($arr['reason'] == 'successed!') {
                                    //var_dump($arr['sk']);
                                    $data = $arr['result'];
                                    return "当前温度：{$data['sk']['temp']}℃,{$data['sk']['wind_direction']}{$data['sk']['wind_strength']},湿度：{$data['sk']['humidity']}，最后更新时间{$data['sk']['time']}";
                                    break;
                                }
                        }
                    }else{
                        $text = new Text(['content'=>'这是我自己发送的文本消息']);
                        return $text;
                    }
                    break;
                case 'image':
                    return '收到图片消息';
                    break;
                case 'voice':
                    return '收到语音消息';
                    break;
                case 'video':
                    return '收到视频消息';
                    break;
                case 'location':
                    /**
                     * $message->Location_X  地理位置纬度
                    $message->Location_Y  地理位置经度
                    $message->Scale       地图缩放大小
                    $message->Label       地理位置信息
                     */
//                    return $message->Location_X.'=='.$message->Location_Y.'='.$message->Scale.'==='.$message->Label;
                    //将用户的位置信息保存到数据中 添加或更新
                    $sql = "insert into location(open_id,x,y,scale,label) VALUES ('{$message->FromUserName}','$message->Location_X','$message->Location_Y','{$message->Scale}','{$message->Label}') ON  DUPLICATE KEY UPDATE x='{$message->Location_X}',y='{$message->Location_Y}',scale='{$message->Scale}',label='{$message->Label}'";
                    M()->execute($sql);
                    return $message->Label;
                    break;
                case 'link':
                    return '收到链接消息';
                    break;
                // ... 其它消息
                default:
                    return '收到其它消息';
                    break;
            }
            // ...
        });
        $server->serve()->send();
    }

    /**
     * 添加微信菜单
     */
    public function addMenu(){
        $app = new Application(C('wechat_config'));
        $menu = $app->menu;
        $buttons = [
            [
                "type" => "click",
                "name" => "最新活动",
                "key"  => "news_activity_list"
            ],
            [
                "name"       => "菜单",
                "sub_button" => [
                    [
                        "type" => "view",
                        "name" => "便民服务",
                        "url"  => "http://www.baidu.com/"
                    ],
                    [
                        "type" => "view",
                        "name" => "小区通知",
                        "url"  => "http://www.qq.com/"
                    ],
                ],
            ],
            [
                'name'=>'个人中心',
                'type'=>'view',
                'url'=>"http://xiaoqu.okiter.com/index.php?s=/Home/Center/index.html"
            ]
        ];
        $menu->add($buttons);
        //获取已经有的菜单
        $menus = $menu->all();
        dump($menus);
    }

    /**
     * 发起授权的方法
     */
    public static function getAccess(){
        if(!session('opend_id')){
            //没有，发起授权
            $app = new \EasyWeChat\Foundation\Application(C('wechat_config'));
            $response = $app->oauth->scopes(['snsapi_base'])
                ->redirect();
            //将请求的路由保存到session中
            session('request_uri',$_SERVER['PATH_INFO']);
            $response->send(); // Laravel 里请使用：return $response;
        }
    }
    /**
     * 授权的回调页面
     * 获取用户的opend_id
     */
    public function callback(){
        $app = new \EasyWeChat\Foundation\Application(C('wechat_config'));
        $user = $app->oauth->user();
//        dump($user);
        // $user 可以用的方法:
        // $user->getId();  // 对应微信的 OPENID
        // $user->getNickname(); // 对应微信的 nickname
        // $user->getName(); // 对应微信的 nickname
        // $user->getAvatar(); // 头像网址
        // $user->getOriginal(); // 原始API返回的结果
        // $user->getToken(); // access_token， 比如用于地址共享时使用

        //将用户的opend_id保存到session中
        session('opend_id',$user->getId());
        $this->redirect(session('request_uri'));
    }
    public function cs(){
        $ajax = new curlAjax();
        $str = urlencode(I('get.city'));
        //dump($str);exit;
        $json = $ajax->httpGet('http://v.juhe.cn/weather/index?format=2&cityname='.$str.'&key=290b7187396a3e88e3fc3a96a91f623e','');
        $arr = json_decode($json,true);
        //dump($arr);exit;
        if($arr['reason']=='successed!'){
            //var_dump($arr['sk']);
            $data = $arr['result'];
            echo I('get.city')."当前温度：{$data['sk']['temp']}℃,{$data['sk']['wind_direction']}{$data['sk']['wind_strength']},湿度：{$data['sk']['humidity']}，最后更新时间{$data['sk']['time']}";
            $today = $data['today'];
            //dump($today);
            echo "<br/>{$today['city']}明天天气预测：<br>{$today['date_y']}-{$today['week']}，温度：{$today['temperature']}，{$today['weather']}，{$today['wind']}穿衣推荐：{$today['dressing_advice']}";
            $future = $data['future'];
            echo "<br>";
            echo "最近一周天气：<br>";
            foreach ($future as $v){
                $int = str_split($v['date']);
                $date = $int[0].$int[1].$int[2].$int[3].'年'.$int[4].$int[5].'月'.$int[6].$int[7].'日';
                echo "<br>
                       {$date}-{$v['week']}，，温度：{$v['temperature']}，{$v['weather']}，{$v['wind']}";
            }
        }
    }
}