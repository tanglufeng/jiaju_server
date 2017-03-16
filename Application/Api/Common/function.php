<?php

/**
 * @author: caipeichao
 */

/**
 * 参数数量任意，返回第一个非空参数
 * @return mixed|null
 */
function alt() {
    for($i = 0 ; $i < func_num_args(); $i++) {
        $arg = func_get_arg($i);
        if($arg) {
            return $arg;
        }
    }
    return null;
}
//
//function array_gets($array, $fields) {
//    $result = array();
//    foreach($fields as $e) {
//        if(array_key_exists($e, $array)) {
//            $result[$e] = $array[$e];
//        }
//    }
//    return $result;
//}

function saveMobileInSession($mobile) {
    session_start();
    session('send_sms', array('mobile'=>$mobile));
}

function getMobileFromSession() {
    return session('send_sms.mobile');
}

function build_code($orderid=""){
//             srand((double)microtime()*1000000);//create a random number feed.
//            $ychar="0,1,2,3,4,5,6,7,8,9,A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z";
//            $list=explode(",",$ychar);
//            for($i=0;$i<6;$i++){
//                $randnum=rand(0,35); // 10+26;
//                $authnum.=$list[$randnum];
//            }
            $code = \Think\String::uuid($orderid);
            $check = M('UcenterMember')->where(array('code'=>$code))->find();//检查当前生产的推广码是否已经被使用
            if(!empty($check)){
                $code = $this->build_code();
            }else{
                return $code;
            }           
}

function time2Units ($time)
{
   $year   = floor($time / 60 / 60 / 24 / 365);
   $time  -= $year * 60 * 60 * 24 * 365;
   $month  = floor($time / 60 / 60 / 24 / 30);
   $time  -= $month * 60 * 60 * 24 * 30;
   $week   = floor($time / 60 / 60 / 24 / 7);
   $time  -= $week * 60 * 60 * 24 * 7;
   $day    = floor($time / 60 / 60 / 24);
   $time  -= $day * 60 * 60 * 24;
   $hour   = floor($time / 60 / 60);
   $time  -= $hour * 60 * 60;
   $minute = floor($time / 60);
   $time  -= $minute * 60;
   $second = $time;
   $elapse = '';

   $unitArr = array('年'  =>'year', '个月'=>'month',  '周'=>'week', '天'=>'day',
                    '小时'=>'hour', '分钟'=>'minute', '秒'=>'second'
                    );

   foreach ( $unitArr as $cn => $u )
   {
       if ( $$u > 0 )
       {
           $elapse = $$u . $cn;
           break;
       }
   }

   return $elapse;
}


/** 
* @method 多维数组转字符串 
* @param type $array 
* @return type $srting 
* @author yanhuixian 
*/  
function arrayToString($arr) {  
if (is_array($arr)){  
return implode(',', array_map('arrayToString', $arr));  
}  
return $arr;  
}  
  
/** 
* @method 多维数组变成一维数组 
* @staticvar array $result_array 
* @param type $array 
* @return type $array 
* @author yanhuixian 
*/  
function ArrMd2Ud($arr) {
 #将数值第一元素作为容器，作地址赋值。
 $ar_room = &$arr[key($arr)];
 #第一容器不是数组进去转呀
 if (!is_array($ar_room)) {
  #转为成数组
  $ar_room = array($ar_room);
 }
 #指针下移
 next($arr);
 #遍历
 while (list($k, $v) = each($arr)) {
  #是数组就递归深挖，不是就转成数组
  $v = is_array($v) ? call_user_func(__FUNCTION__, $v) : array($v);
  #递归合并
  $ar_room = array_merge_recursive($ar_room, $v);
  #释放当前下标的数组元素
  unset($arr[$k]);
 }
 return $ar_room;
} 


//将IP转换为数字
function ipton($ip)
{
    $ip_arr=explode('.',$ip);//分隔ip段
    foreach ($ip_arr as $value)
    {
        $iphex=dechex($value);//将每段ip转换成16进制
        if(strlen($iphex)<2)//255的16进制表示是ff，所以每段ip的16进制长度不会超过2
        {
            $iphex='0'.$iphex;//如果转换后的16进制数长度小于2，在其前面加一个0
        //没有长度为2，且第一位是0的16进制表示，这是为了在将数字转换成ip时，好处理
        }
        $ipstr.=$iphex;//将四段IP的16进制数连接起来，得到一个16进制字符串，长度为8
    }
    return hexdec($ipstr);//将16进制字符串转换成10进制，得到ip的数字表示
}
 
 
//将数字转换为IP，进行上面函数的逆向过程
function ntoip($n)
{
    $iphex=dechex($n);//将10进制数字转换成16进制
    $len=strlen($iphex);//得到16进制字符串的长度
    if(strlen($iphex)<8)
    {
        $iphex='0'.$iphex;//如果长度小于8，在最前面加0
        $len=strlen($iphex); //重新得到16进制字符串的长度
    }
    //这是因为ipton函数得到的16进制字符串，如果第一位为0，在转换成数字后，是不会显示的
    //所以，如果长度小于8，肯定要把第一位的0加上去
    //为什么一定是第一位的0呢，因为在ipton函数中，后面各段加的'0'都在中间，转换成数字后，不会消失
    for($i=0,$j=0;$j<$len;$i=$i+1,$j=$j+2)
    {//循环截取16进制字符串，每次截取2个长度
        $ippart=substr($iphex,$j,2);//得到每段IP所对应的16进制数
        $fipart=substr($ippart,0,1);//截取16进制数的第一位
        if($fipart=='0')
        {//如果第一位为0，说明原数只有1位
            $ippart=substr($ippart,1,1);//将0截取掉
        }
        $ip[]=hexdec($ippart);//将每段16进制数转换成对应的10进制数，即IP各段的值
    }
    $ip = array_reverse($ip);
     
    return implode('.', $ip);//连接各段，返回原IP值
}

function strSplit($str, $data = array('num' => 4, 'num2' => 6, 'num3' => 5), $sle = "-") {
    $len = mb_strlen($str, 'utf-8'); //获取字符串长度 每个汉字算1
    $arr = 0;

    foreach ($data as $key => $value) {
        $strs[] = substr($str, $arr, $value);
        $arr += $value;
    }
    return arr2str($strs, '-');
}

//将where条件数组中的key带上表名
function whereAddTableName($where=[], $table_name){
	foreach ($where as $k => $v){
		if(false === strpos($k, '.') && false === strpos($k, $table_name)){
			$k2 =  $table_name. '.' .$k;
			$where[$k2] = $v;
			unset($where[$k]);
		}
	}
	return $where;
}

