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

class Weixin implements Pay
{
    private $parameters = array();
    private $key;//密匙

    public function __construct($appid, $mchid, $key)
    {
        $this->key = $key;
        $this->parameters['appid'] = $appid;
        $this->parameters['mch_id'] = $mchid;

        $this->parameters['fee_type'] = 'CNY';
    }

    public function payReturn()
    {
        // TODO: Implement payReturn() method.
    }

    public function payNotify()
    {
        $data = file_get_contents("php://input");
        if ($result = $this->xml2arr($data)) {
            if (isset($result['sign'])) {
                if ($this->getSign($result) === $result['sign']) {
                    return $result;
                }
            }
        }
        return false;
    }

    public function paySubmit($orderId, $title, $rmb, $description, $ip = null, $mobile = false)
    {
        $rmb = intval($rmb * 100);
        $this->parameters['nonce_str'] = md5(time() . rand(10000, 99999));
        $this->parameters['body'] = substr($title, 0, 128);
        $this->parameters['attach'] = $description;
        $this->parameters['out_trade_no'] = $orderId;
        $this->parameters['total_fee'] = $rmb;
        if ($mobile) {
            $this->parameters['trade_type'] = 'MWEB';
            $this->parameters['spbill_create_ip'] = $ip;
            $this->parameters['scene_info'] = '{"h5_info": {"type":"Wap","wap_url": "","wap_name": "在线充值"}}';
        } else {
            $this->parameters['trade_type'] = 'NATIVE';
            $this->parameters['spbill_create_ip'] = $_SERVER['REMOTE_ADDR'];
        }
        $sign = $this->getSign($this->parameters);
        $this->parameters['sign'] = $sign;
        $data = $this->getCurl("https://api.mch.weixin.qq.com/pay/unifiedorder", $this->getPostXml($this->parameters));
        if ($result = $this->xml2arr($data)) {
            if (isset($result['code_url'])) {
                return $result['code_url'];
            }
        }
        return false;
    }

    public function setNotifyUrl($url)
    {
        $this->parameters['notify_url'] = $url;
    }

    public function setReturnUrl($url)
    {
        // TODO: Implement setReturnUrl() method.
    }

    public function setParameter($k, $v)
    {
        $this->parameters[$k] = $v;
    }

    public function orderQuery($orderId)
    {
        $url = "https://api.mch.weixin.qq.com/pay/orderquery";
        $parameters = array(
            'appid' => $this->parameters['appid'],
            'mch_id' => $this->parameters['mch_id'],
            'nonce_str' => md5(time() . rand(10000, 99999)),
            'out_trade_no' => $orderId,
        );
        $parameters['sign'] = $this->getSign($parameters);
        $data = $this->getCurl($url, $this->getPostXml($parameters));
        if ($result = $this->xml2arr($data)) {
            if (isset($result['trade_state']) && $result['trade_state'] == 'SUCCESS') {
                return $result;
            }
        }
        return false;
    }

    private function getSign($parameters)
    {
        $signPars = "";
        ksort($parameters);
        foreach ($parameters as $k => $v) {
            if ("sign" != $k && "" != $v) {
                $signPars .= $k . "=" . $v . "&";
            }
        }
        $signPars .= "key=" . $this->key;
        $sign = strtoupper(md5($signPars));
        return $sign;
    }

    private function getPostXml($parameters)
    {
        $xml = "<xml>";
        foreach ($parameters as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }

    private function xml2arr($data)
    {
        $array = array();
        if ($xml = simplexml_load_string($data)) {
            if ($xml->children()) {
                foreach ($xml->children() as $node) {
                    //有子节点
                    if ($node->children()) {
                        $k = $node->getName();
                        $nodeXml = $node->asXML();
                        $v = substr($nodeXml, strlen($k) + 2, strlen($nodeXml) - 2 * strlen($k) - 5);
                    } else {
                        $k = $node->getName();
                        $v = (string)$node;
                    }
                    $array[$k] = $v;
                }
                return $array;
            }
        }
        return false;
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