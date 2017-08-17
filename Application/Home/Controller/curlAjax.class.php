<?php
namespace Home\Controller;
//定义一个类文件 curl扩展实现发送请求
class curlAjax
{
    //定义存储cookie数据的jar
    private $cookijar;
    //定义存储cookie数据的文件
    private $cookiefile;
    //定义发送请求的代理用户
    private $ua;
    //定义存储bug的变量
    private $debug;

    //以get方式发送请求
    /*
     * @param string $url 地址
     * @param string $referer 请求来源
     * @param boolen $withhead 是否返回头部信息
     */
    public function httpGet($url,$referer,$withhead=false){
        //初始化一个会话
        $ch = curl_init();
        $options = array(
            //设置请求的地址
            CURLOPT_URL=>$url,
            //将获取到的cookie保存在指定文件中
            CURLOPT_COOKIEJAR=>$this->cookijar,
            //将文件中的cookie数据带去请求页面
            CURLOPT_COOKIEFILE=>$this->cookiefile,
            //是有具有返回头
            CURLOPT_HEADER=>$withhead,
            //已流文件的形式返回
            CURLOPT_RETURNTRANSFER=>1,
            //设置是否继续抓取重定向的页面
            CURLOPT_FOLLOWLOCATION=>1,
            //设置用户代理
            CURLOPT_USERAGENT=>$this->ua,
            //设置用户的来源
            CURLOPT_REFERER=>$referer,
        );
        curl_setopt_array($ch,$options);
        //执行发送请求 返回抓取到的数据内容
        $contents = curl_exec($ch);
        //判断是否获取curl连接句柄的信息
        //关闭资源
        curl_close($ch);
        return $contents;
    }
    //做一些初始化的操作
    public function __construct($params=array()){
        $this->ua=isset($params['ua'])?$params['ua']:'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.85 Safari/537.36';
        $this->cookiejar=isset($params['cookiefile'])?$params['cookiefile']:'./cookie.txt';
        $this->cookiefile=isset($params['cookiefile'])?$params['cookiefile']:'./cookie.txt';
        $this->debug=isset($params['debug'])?$params['debug']:true;
    }
    //post方式提交数据
    /*
     * @param string $url 请求地址
     * @param string $referer 访问来源
     * @param array $postData 发送的数据
     * @param boolen $withhead 是否需要请求头
     */
    public function httpPost($url,$referer,$postData=array(),$withHead=false){
        //开启一个会话机制
        $ch = curl_init();
        //设置参数信息
        $options = array(
            //设置请求的连接
            CURLOPT_URL=>$url,
            //设置是否返回头
            CURLOPT_HEADER=>$withHead,
            //设置抓取的内容以什么样的形式返回
            CURLOPT_RETURNTRANSFER=>1,
            //设置提交请求的方式
            CURLOPT_POST=>1,
            //设置提交的数据
            CURLOPT_POSTFIELDS=>$postData,
            //设置请求来源
            CURLOPT_REFERER=>$referer,
            //设置请求代理
            CURLOPT_USERAGENT=>$this->ua,
            //设置cookie的保存路径
            CURLOPT_COOKIEJAR=>$this->cookiejar,
            //设置cookie数据读取的文件
            CURLOPT_COOKIEFILE=>$this->cookiefile,
            //备注这里两次I/O操作 效率低下
            //设置抓取重定向的页面
            CURLOPT_FOLLOWLOCATION=>1
        );
        curl_setopt_array($ch,$options);
        $contents = curl_exec($ch);
        //获取curl的句柄的详细信息
        return $contents;
    }
}