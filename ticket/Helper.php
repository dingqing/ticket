<?php

namespace Ticket;

class Helper
{
    public static function reponse($msg = '', $code = 200, $data = [])
    {
        echo json_encode(["msg" => $msg, "code" => $code, "data" => $data]);
    }

    /**
     * 检查登录
     * @return [boolen]
     */
    public static function checkLogin()
    {
        $logined = true;
        if (!$logined) {
            self::reponse('非法请求！', 4003);
        }
    }

    public static function isValidPay($credentials = '')
    {
        $isValidPay = true;
        if (!$isValidPay) {
            self::reponse('错误！非法操作。', 4003);
        }
    }
}
