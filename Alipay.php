<?php
// +----------------------------------------------------------------------
// | Quotes [ 当初的热烈终将被生活磨平归于平淡，你所厌倦的或许正是别人所期待的 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2021 https://blog.dawnwl.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 吕梓赫 <10001@682o.com>
// +----------------------------------------------------------------------
// | Date: 2021/11/10
// +----------------------------------------------------------------------

class Alipay implements Pay
{
    private $parameters = array();
    private $alipay_gateway_url = "https://openapi.alipay.com/gateway.do";
    private $private_rsa_key;
    private $public_rsa_key;

    public function __construct($appid, $private_rsa_key, $public_rsa_key)
    {
        $this->parameters['app_id'] = $appid;
        $this->parameters['charset'] = 'utf-8';
        $this->parameters['sign_type'] = 'RSA2';
        $this->parameters['timestamp'] = date("Y-m-d H:i:s");
        $this->parameters['version'] = '1.0';
        $this->private_rsa_key = $private_rsa_key;
        $this->public_rsa_key = $public_rsa_key;
    }

    public function payReturn()
    {
        if (!empty($_GET)) {
            if ($this->signVerify($_GET)) {
                return true;
            }
        }
        return false;
    }

    public function payNotify()
    {
        if (!empty($_POST)) {
            if ($this->signVerify($_POST)) {
                return true;
            }
        }
        return false;
    }

    public function paySubmit($orderId, $title, $rmb, $description = null, $ip = null, $mobile = false)
    {
        if ($mobile) {
            $this->parameters['method'] = 'alipay.trade.wap.pay';
        }else{
            $this->parameters['method'] = 'alipay.trade.page.pay';
        }
        $this->parameters['biz_content'] = json_encode([
            'out_trade_no' => $orderId,
            'subject' => $title,
            'body' => $description,
            'total_amount' => $rmb,
            'product_code' => ($mobile == 0) ? 'FAST_INSTANT_TRADE_PAY' : 'QUICK_WAP_PAY'
        ]);

        $this->parameters['sign'] = $this->getSign($this->parameters);

        return $this->alipay_gateway_url . '?' .$this->arr2str($this->parameters);
    }

    public function setNotifyUrl($url)
    {
        // 服务器异步通知页面路径  需http://格式的完整路径，不能加?id=123这类自定义参数，必须外网可以正常访问
        $this->parameters['notify_url'] = $url;
    }

    public function setReturnUrl($url)
    {
        // 页面跳转同步通知页面路径 需http://格式的完整路径，不能加?id=123这类自定义参数，必须外网可以正常访问
        $this->parameters['return_url'] = $url;
    }

    public function setParameter($k, $v)
    {
        $this->parameters[$k] = $v;
    }

    public function orderQuery($orderId)
    {
        $this->parameters['method'] = 'alipay.trade.query';
        $this->parameters['biz_content'] = json_encode([
            'out_trade_no' => $orderId
        ]);
        $this->parameters['sign'] = $this->getSign($this->parameters);
        $ret = $this->getCurl($this->alipay_gateway_url, $this->arr2str($this->parameters));
            $arr = json_decode($ret, true);
            $status = isset($arr['alipay_trade_query_response']['trade_status']) ? $arr['alipay_trade_query_response']['trade_status'] : null;
            if ($status == 'TRADE_SUCCESS' || $status == 'TRADE_FINISHED') {
                return $arr['alipay_trade_query_response'];
            }
        return false;
    }

    private function signVerify($parameters)
    {
        $signPars = "";
        ksort($parameters);

        foreach ($parameters as $k => $v) {
                if ("sign" != $k && "sign_type" != $k && "" != $v) {
                    $signPars .= $k . "=" . $v . "&";
                }
            }
            $signPars = trim($signPars, '&');
        if($pi_key = openssl_pkey_get_public($this->public_rsa_key)) {
            if(openssl_verify($signPars, base64_decode($parameters['sign']), $pi_key, OPENSSL_ALGO_SHA256)) {
                openssl_free_key($pi_key);
                return true;
            }
        }
        return false;
    }

    private function getSign($parameters)
    {
        $signPars = "";
        ksort($parameters);

        foreach ($parameters as $k => $v) {
                if ("sign" != $k) {
                    $signPars .= $k . "=" . $v . "&";
                }
            }
            $signPars = trim($signPars, '&');
            $sign = "";
            if ($pi_key = openssl_pkey_get_private($this->private_rsa_key)) {
                if (openssl_sign($signPars, $sign, $pi_key, OPENSSL_ALGO_SHA256)) {
                    openssl_free_key($pi_key);
                    $sign = urlencode(base64_encode($sign));
                    return $sign;
                }
            }
            return false;
    }

    private function arr2str($parameters)
    {
        $str = "";
        foreach ($parameters as $k => $v) {
            $str .= "&{$k}=" . $v;
        }
        return trim($str, '&');
    }

    private function getCurl($url, $post = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        if ($post) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        curl_setopt($ch, CURLOPT_ENCODING, "gzip");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $ret = curl_exec($ch);
        curl_close($ch);
        return $ret;
    }

}