<?php

namespace Common\Controller;

use Think\Controller;

ini_set('date.timezone', 'Asia/Shanghai');
error_reporting(E_ERROR);
import('Common.Wxpay.lib.WxPay#Api', APP_PATH, '.php');
import('Common.Wxpay.lib.WxPay#Notify', APP_PATH, '.php');
require_once 'log.php';

//初始化日志
$logHandler = new \CLogFileHandler("../logs/" . date('Y-m-d') . '.log');
$log = \Log::Init($logHandler, 15);

class PayNotifyCallBack extends \WxPayNotify {

//查询订单
    public function Queryorder($transaction_id) {
        $input = new \WxPayOrderQuery();
        $input->SetTransaction_id($transaction_id);
        $result = \WxPayApi::orderQuery($input);
        $this->log = new \Log\PhpLog($_SERVER['DOCUMENT_ROOT'] . '/notify/', 'PRC', 'LogInfo');

        $this->log->LogInfo("query:" . json_encode($result));
        if (array_key_exists("return_code", $result) && array_key_exists("result_code", $result) && $result["return_code"] == "SUCCESS" && $result["result_code"] == "SUCCESS") {
            return true;
        }
        return false;
    }

//重写回调处理函数
    public function NotifyProcess($data, &$msg) {
        $this->log = new \Log\PhpLog($_SERVER['DOCUMENT_ROOT'] . '/notify/', 'PRC', 'LogInfo');

        $this->log->LogInfo('call back:' . json_encode($data) . "验证" . !array_key_exists("transaction_id", $data) . "订单" . !$this->Queryorder($data["transaction_id"]));
//        \Log::DEBUG("call back:" . json_encode($data));
        $notfiyOutput = array();

        if (!array_key_exists("transaction_id", $data)) {
            $msg = "输入参数不正确";
            return false;
        }
//查询订单，判断订单真实性
        if (!$this->Queryorder($data["transaction_id"])) {
            $msg = "订单查询失败";
            return false;
        }

        $this->log->LogInfo('准备写入更新数据库' . json_encode($data));
        //支付成功后的逻辑操作
        //订单号
        $order_id = $data['out_trade_no'];
        //微信交易号
        $transaction_id = $data['transaction_id'];

        //openid
        $openid = $data['openid'];

        //交易金额，单位分
        $price = $data['total_fee'] * 0.01;

        $attach = $data['attach'];
        //交易完成时间
        $finish_time = $data['time_end'];
        $finish_time = date('Y-m-d H:i:s', strtotime($finish_time));

        //验证订单合法性
        if (!$order_id) {
            exit('缺少必要参数');
        }

        //订单回调
        $map1['order_id'] = $order_id;
        $list = M('ShopOrder')->where($map1)->find();
        $this->log->LogInfo('数据库：' . json_encode($list));
        if (!$list) {
            exit('该订单不存在');
        }

//        if ($order_info['wxzf'] != $price) {
//            exit('订单金额有误');
//        }
        // 订单已取消
        if ($list['status'] == -1) {
            exit('该订单已取消');
        }

        // 已经支付，无需再次支付
        if ($list['status'] == 1) {
            exit('该订单已经支付');
        }

        // 不是待支付状态
        if ($list['status'] == 2) {
            exit('该订单已经发货');
        }

        // 订单被删除
        if ($list['status'] == 3) {
            exit('该订单已完成，无需支付！');
        }


        $data['status'] = 1;
        $map['order_id'] = $order_id;
        $data['pay_num'] = $transaction_id;
        $data['pay_time'] = NOW_TIME;
        $order_info = M('ShopOrder')->where($map)->save($data);
        M('ShopOrderList')->where($map)->save(array('status' => 1));
        if ($order_info) {
            $t_url = 'http://lshcn.com/#main/order';
            $openid = $data['openid'];
            $goods_name = '订单号：' . $order_id;
            $money = $price;
            $t_msg = '恭喜你购买成功！';
            $m_msg = '欢迎再次购买！';
            $goods_pay = '【微信支付】\n收货信息：' . $list['address'] . '\n\n手机号：' . $list['moblie'] . '\n\n';
            $wechat = new \Log\wechat();
            $a = $wechat->send_model($t_url, $openid, $goods_name, $money, $t_msg, $m_msg, $goods_pay);
        }

        return true;
    }

}

//Log::DEBUG("begin notify");
//$notify = new PayNotifyCallBack();
//$notify->Handle(false);
