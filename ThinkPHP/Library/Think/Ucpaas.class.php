<?php
/**
 * Created by PhpStorm.
 * User: UCPAAS
 * Date: 2015/09/18
 * Time: 12:04
 * Dec : ucpass php sdk
 */
namespace Think;
class Ucpaas
{

    /**
     *  云之讯REST API版本号。当前版本号为：2015-06-30
     */
    const SoftVersion = "2015-06-30";
    /**
     * 云之讯REST API请求地址
     */
    const BaseUrl = "https://api.ucpaas.com/";
    /**
     * 开发者账号ID。由32个英文字母和阿拉伯数字组成的开发者账号唯一标识符。
     */
    private $accountSid;
    /**
     * 开发者授权令牌TOKEN
     */
    private $token;
    /**
     * 时间戳
     */
    private $timestamp;


    /**
     * @param $options 数组参数必填
     * @throws Exception
     */
    public function  __construct($options)
    {
        if (is_array($options) && !empty($options)) {
            $this->accountSid = isset($options['accountSid']) ? $options['accountSid'] : '';
            $this->token = isset($options['token']) ? $options['token'] : '';
            date_default_timezone_set("Asia/Shanghai");
            $this->timestamp = date("YmdHis");
        } else {
            throw new Exception("非法参数");
        }
    }

    /**
     * 获取权限验证码Authorization
     * 包头验证信息,使用Base64编码，格式为：base64（账户Id:时间戳）
     */
    private function getAuthorization()
    {
        $data = $this->accountSid . ":" . $this->timestamp;
        return trim(base64_encode($data));
    }

    /**
     * 获取sig签名串
     * 验证参数,URL后必须带有sig参数，sig= MD5（账户Id + 账户授权令牌 + 时间戳，共32位）(注:转成大写)
     */
    private function getSigParameter()
    {
        $sig = $this->accountSid . $this->token . $this->timestamp;
        return strtoupper(md5($sig));
    }

    /**
     * 获取结果
     * @param $url Rest API请求地址
     * @param null $body 请求地址中的包体内容
     * @param string $type json或xml类型格式
     * @param $method 请求方法
     * @return mixed|string
     */
    private function getResult($url, $body = null, $type = 'json',$method)
    {
        $data = $this->connection($url,$body,$type,$method);
        if (isset($data) && !empty($data)) {
            $result = $data;
        } else {
            $result = '{"resp":{"respCode":"106900"}}';
        }
        return $result;
    }

    /**
     * Http发送请求连接
     * @param $url Rest API请求地址
     * @param $type json或xml类型格式
     * @param $body 请求地址中的包体内容
     * @param $method 请求方法
     * @return mixed|string 返回指定$type格式的数据
     */
    private function connection($url, $body, $type,$method)
    {
        if ($type == 'json') {
            $mine = 'application/json';
        } else {
            $mine = 'application/xml';
        }
                    
        if (function_exists("curl_init")) {
            $header = array(
                'Accept:' . $mine,
                'Content-Type:' . $mine . ';charset=utf-8',
                'Authorization:' . $this->getAuthorization(),
            );
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            if($method == 'post'){
                curl_setopt($ch,CURLOPT_POST,1);
                curl_setopt($ch,CURLOPT_POSTFIELDS,$body);
            }
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $result = curl_exec($ch);
            curl_close($ch);
        } else {
            $opts = array();
            $opts['http'] = array();
            $headers = array(
                "method" => strtoupper($method),
            );
            $headers[]= 'Accept:'.$mine;
            $headers['header'] = array();
            $headers['header'][] = "Authorization: ".$this->getAuthorization();
            $headers['header'][]= 'Content-Type:'.$mine.';charset=utf-8';

            if(!empty($body)) {
                $headers['header'][]= 'Content-Length:'.strlen($body);
                $headers['content']= $body;
            }

            $opts['http'] = $headers;
            $result = file_get_contents($url, false, stream_context_create($opts));
        }
        
        return $result;     
    }

    /**
     * 子账号服务-创建子账号
     * @param $appId  app应用ID
     * @param $friendlyName 昵称
     * @param $mobile 手机号码
     * @param $userId 用户注册子账号输入的userid，原则上跟手机号码相同。同一个应用内唯一·
     * @param string $type 默认json,也可指定xml,否则抛出异常
     * @return mixed|string 返回指定$type格式的数据
     * @throws Exception
     */
    public function createClient($appId, $friendlyName, $mobile, $userId, $type = 'json')
    {
        $url = self::BaseUrl . self::SoftVersion . '/Accounts/' . $this->accountSid . '/Clients?sig=' . $this->getSigParameter();
        if ($type == 'json') {
            $body_json = array();
            $body_json['client'] = array();
            $body_json['client']['appId'] = $appId;
            $body_json['client']['friendlyName'] = $friendlyName;
            $body_json['client']['mobile'] = $mobile;
            $body_json['client']['userId'] = $userId;
            $body = json_encode($body_json);
        } elseif ($type == 'xml') {
            $body_xml = '<?xml version="1.0" encoding="utf-8"?>
                        <client><appId>'.$appId.'</appId>
                        <friendlyName>'.$friendlyName.'</friendlyName>
                        <mobile>'.$mobile.'</mobile>
                        <userId>'.$userId.'</userId>
                        </client>';
            $body = trim($body_xml);
        } else {
            throw new Exception("只能json或xml，默认为json");
        }
        $data = $this->getResult($url, $body, $type,'post');
        return $data;
    }

    /**
     * 子账号服务-释放子账号
     * @param $userId  用户注册子账号输入的userid，原则上跟手机号码相同。同一个应用内唯一·
     * @param $appId   app应用ID
     * @param string $type  默认json,也可指定xml,否则抛出异常
     * @return mixed|string 返回指定$type格式的数据
     * @throws Exception
     */
    public function releaseClient($userId,$appId,$type = 'json'){
        $url = self::BaseUrl . self::SoftVersion . '/Accounts/' . $this->accountSid . '/dropClient?sig=' . $this->getSigParameter();
        if($type == 'json'){
            $body_json = array();
            $body_json['client'] = array();
            $body_json['client']['appId'] = $appId;
            $body_json['client']['userId'] = $userId;
            $body = json_encode($body_json);
        }elseif($type == 'xml'){
            $body_xml = '<?xml version="1.0" encoding="utf-8"?>
                        <client>
                        <userId>'.$userId.'</userId>
                        <appId>'.$appId.'</appId >
                        </client>';
            $body = trim($body_xml);
        }else {
            throw new Exception("只能json或xml，默认为json");
        }
        $data = $this->getResult($url, $body, $type,'post');
        return $data;
    }

    /**
     * 子账号服务-通过手机号获取子账号
     * @param $appId       app应用ID
     * @param $mobile      手机号码
     * @param string $type 默认json,也可指定xml,否则抛出异常
     * @return mixed|string返回指定$type格式的数据
     * @throws Exception
     */
    public function getClientInfoByMobile($appId,$mobile,$type = 'json'){
        if ($type == 'json') {
            $type = 'json';
        } elseif ($type == 'xml') {
            $type = 'xml';
        } else {
            throw new Exception("只能json或xml，默认为json");
        }
        $url = self::BaseUrl . self::SoftVersion . '/restApi/' . $this->accountSid . '/ClientsByMobile?sig=' . $this->getSigParameter(). '&mobile='.$mobile.'&appId='.$appId;
        $data = $this->getResult($url,null,$type,'get');
        return $data;
    }

    /**
     * 子账号服务-通过useId获取子账号
     * @param $appId          app应用ID
     * @param $userId         用户注册子账号输入的userid，原则上跟手机号码相同。同一个应用内唯一·
     * @param string $type    默认json,也可指定xml,否则抛出异常
     * @return mixed|string   返回指定$type格式的数据
     * @throws Exception
     */
    public function getClientInfoByUserId($appId,$userId,$type = 'json'){
        if ($type == 'json') {
            $type = 'json';
        } elseif ($type == 'xml') {
            $type = 'xml';
        } else {
            throw new Exception("只能json或xml，默认为json");
        }
        $url = self::BaseUrl . self::SoftVersion . '/restApi/' . $this->accountSid . '/ClientsByUserId?sig=' . $this->getSigParameter(). '&userId='.$userId.'&appId='.$appId;
        $data = $this->getResult($url,null,$type,'get');
        return $data;
    }

    /**
     * 群组服务-创建群组
     * @param $appId          APP应用ID
     * @param $userId         用户注册子账号输入的userId，原则上跟手机号码相同。同一个应用内唯一·
     * @param $groupId        群组ID
     * @param $groupName      群组名称
     * @param string $type    默认json,也可指定xml,否则抛出异常
     * @return mixed|string   返回指定$type格式的数据
     */
    public function imCreateGroup($appId,$userId,$groupId,$groupName,$type = 'json'){
        return $this->imGroupOperation($appId,$userId,$groupId,$groupName,'/im/group/createGroup',$type);
    }

    /**
     * 群组服务-释放群组
     * @param $appId          APP应用ID
     * @param $groupId        群组ID
     * @param string $type    默认json,也可指定xml,否则抛出异常
     * @return mixed|string   返回指定$type格式的数据
     */
    public function imDismissGroup($appId,$groupId,$type = 'json'){
        return $this->imGroupOperation($appId,null,$groupId,null,'/im/group/dismissGroup',$type);
    }

    /**
     * 群组服务-加入群组
     * @param $appId          APP应用ID
     * @param $userId         用户注册子账号输入的userId，原则上跟手机号码相同。同一个应用内唯一·（这里是数组集合）
     * @param $groupId        群组ID
     * @param string $type    默认json,也可指定xml,否则抛出异常
     * @return mixed|string   返回指定$type格式的数据
     */
    public function imJoinGroupBatch($appId,$userId,$groupId,$type = 'json'){
        return $this->imGroupOperation($appId,$userId,$groupId,null,'/im/group/joinGroupBatch',$type);
    }

    /**
     * 群组服务-退出群组
     * @param $appId          APP应用ID
     * @param $userId         用户注册子账号输入的userId，原则上跟手机号码相同。同一个应用内唯一·
     * @param $groupId        群组ID
     * @param string $type    默认json,也可指定xml,否则抛出异常
     * @return mixed|string   返回指定$type格式的数据
     */
    public function imQuitGroup($appId,$userId,$groupId,$type = 'json'){
        return $this->imGroupOperation($appId,$userId,$groupId,null,'/im/group/quitGroup',$type);
    }

    /**
     * 群组服务-更新群组
     * @param $appId          APP应用ID
     * @param $groupId        群组ID
     * @param $groupName      群组名称
     * @param string $type    默认json,也可指定xml,否则抛出异常
     * @return mixed|string   返回指定$type格式的数据
     */
    public function imUpdateGroup($appId,$groupId,$groupName,$type = 'json'){
        return $this->imGroupOperation($appId,null,$groupId,$groupName,'/im/group/updateGroup',$type);
    }

    /**
     * 群组服务-查询群组信息
     * @param $appId          APP应用ID
     * @param $groupId        群组ID
     * @param string $type    默认json,也可指定xml,否则抛出异常
     * @return mixed|string   返回指定$type格式的数据
     */
    public function imGetGroup($appId,$groupId,$type = 'json'){
        return $this->imGroupOperation($appId,null,$groupId,null,'/im/group/getGroup',$type);
    }

    /**
     * 群组服务-群组操作共用的方法
     * @param $appId          APP应用ID
     * @param $userId         用户注册子账号输入的userId，原则上跟手机号码相同。同一个应用内唯一·
     * @param $groupId        群组ID
     * @param $groupName      群组名称
     * @param $path           群组REST API接口请求映射路径
     * @param string $type    默认json,也可指定xml,否则抛出异常
     * @return mixed|string   返回指定$type格式的数据
     * @throws Exception
     */
    public function imGroupOperation($appId,$userId,$groupId,$groupName,$path,$type = 'json'){
        $url = self::BaseUrl . self::SoftVersion . '/Accounts/' . $this->accountSid . $path . '?sig=' . $this->getSigParameter();
        if ($type == 'json') {
            $body_json = array();
            $body_json['imGroup'] = array();
            $body_json['imGroup']['appId'] = $appId;
            $body_json['imGroup']['userId'] = $userId;
            $body_json['imGroup']['groupId'] = $groupId;
            $body_json['imGroup']['groupName'] = $groupName;
            $body = json_encode($body_json);
        }elseif ($type == 'xml') {
            $body_xml = '<?xml version="1.0" encoding="utf-8"?>
                        <imGroup><appId>'.$appId.'</appId>
                        <userId>'.$userId.'</userId>
                        <groupId>'.$groupId.'</groupId>
                        <groupName>'.$groupName.'</groupName>
                        </imGroup>';
            $body = trim($body_xml);
        } else {
            throw new Exception("只能json或xml，默认为json");
        }
        $data = $this->getResult($url, $body, $type,'post');
        return $data;
    }
} 