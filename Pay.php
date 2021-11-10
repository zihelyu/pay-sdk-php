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

interface Pay
{
    public function payReturn();

    public function payNotify();

    public function paySubmit($orderId, $title, $rmb, $description, $ip, $mobile);

    public function setNotifyUrl($url);

    public function setReturnUrl($url);

    public function setParameter($k, $v);

    public function orderQuery($orderId);

}