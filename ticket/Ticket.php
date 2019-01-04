<?php

namespace Ticket;

use Medoo\Medoo;

/**
 * @authors dingqing
 * @version 1.0.0
 */
class Ticket
{
    public function __construct(Medoo $db)
    {
        $this->db = $db;
    }

    /**
     * 谨慎使用！！用于初始向数据库空表插入所有座位票数据
     * @return [array]
     */
    public function initTickets()
    {
        // 看台座位设置
        $widthFirst = 50;
        $widthLast = 100;
        $widthIncrement = 2;
        $areas = range('A', 'D');

        // 生成票
        $tickets = [];
        $rows = ($widthLast - $widthFirst) / $widthIncrement + 1;
        foreach ($areas as $valA) {// 区
            for ($i = 1; $i <= $rows; $i++) {// 排
                $rowWidth = $widthFirst + ($i - 1) * $widthIncrement;
                for ($j = 1; $j <= $rowWidth; $j++) {// 列
                    $tickets[] = [
                        'ticket' => $valA . $i . '-' . $j,
                        'status' => 1,
                    ];
                }
            }
        }

        $existNum = $this->db->count("tickets");
        if ($existNum) {
            Helper::reponse('错误！不是空表。请保存原有数据之后继续操作。', 4002);
        }

        /*插入数据库*/
        $dbResult = $this->db->insert("tickets", $tickets);
        Helper::reponse('成功插入' . $dbResult->rowCount() . '条！');
    }

    // 查询余票
    public function inquireTickets()
    {
        Helper::checkLogin();

        $remainingNum = $this->db->count("tickets", [
            "status" => 1,
        ]);
        Helper::reponse('还有余票' . $remainingNum . '张。');
    }

    /**
     * 出票
     * @param  [integer] $uid
     * @param  [integer] $buyNum 购买张数
     * @return [array]
     */
    public function buyTickets($uid, $buyNum = 1)
    {
        Helper::checkLogin();

        $maxNum = 5;
        /*张数限制*/
        if ($buyNum > $maxNum) {
            Helper::reponse('最多允许购买' . $maxNum . '张票。', 4001);
        }

        // 购票检查：历史购买 + 本次购买 不允许超过 购票限制
        /*$boughtNum = $this->db->count("tickets", [
            "uid"       => $uid,
            "status[!]" => 1,
        ]);

        $canBuyNum = $maxNum - $boughtNum;
        if ($canBuyNum < $buyNum) {
            Helper::reponse('出错！由于您已购'.$boughtNum.'张票，最多还允许购买'.$canBuyNum.'张。', 4001);
        }*/

        $remainingNum = $this->db->count("tickets", [
            "status" => 1,
        ]);
        if (!$remainingNum) {
            Helper::reponse('啊哦！你来晚了，票已被抢光，下次要早点哟。');
        }

        $msgExtra = '';
        $offset = mt_rand(0, $remainingNum - $buyNum);
        if ($remainingNum < $buyNum) {
            $msgExtra = '（由于余票张数小于您购买张数）';
            $offset = 0;
            $buyNum = $remainingNum;
        }

        /*出票*/
        $ticketsReturn = $this->db->select("tickets", 'ticket', [
            "status" => 1,
            "LIMIT" => [$offset, $buyNum],
        ]);

        $ticketsReturnStr = implode('，', $ticketsReturn);

        // 查询出票座位是否相邻（同区、同排），让客户端询问用户是否继续出票
        if ($buyNum > 1) {
            $notSameRowMsg = '';

            $ticket0Arr = explode('-', $ticketsReturn[0]); //['C19', '22']
            $ticket0Row = $ticket0Arr[0]; //'C19'
            $ticket0Area = substr($ticket0Row, 0, 1); //'C'
            foreach ($ticketsReturn as $val) {
                $ticketArr = explode('-', $val);
                $ticketRow = $ticketArr[0];
                $ticketArea = substr($ticketRow, 0, 1);
                if ($ticketArea != $ticket0Area) {
                    $notSameRowMsg = '座位不相邻（不在同一区）：' . $ticketsReturnStr . '，是否继续？';
                } else {
                    if ($ticketRow != $ticket0Row) {
                        $notSameRowMsg = '座位不相邻（不在同一排）：' . $ticketsReturnStr . '，是否继续？';
                    }
                }

                if ($notSameRowMsg) {
                    Helper::reponse($notSameRowMsg, 4004);
                    break;
                }
            }
        }

        /*更新票记录*/
        $res = $this->db->update("tickets", [
            'status' => 2,
            "uid" => $uid,
            'time' => date('Y-m-d H:i:s'),
        ], [
            "ticket" => $ticketsReturn,
        ]);

        $rowCount = $res->rowCount();
        if ($rowCount) {
            Helper::reponse('恭喜！' . $msgExtra . '成功购得' . $buyNum . '张票：' . $ticketsReturnStr);
        } else {
            Helper::reponse('系统忙，出票失败，请稍后重试。', 5001);
        }
    }

    /**
     * 查询用户未完成/已完成订单
     * @param  [string]  $uid
     * @param  [integer] $status 3/2
     * @return [array]
     */
    public function userOrder($uid = '', $status = 3)
    {
        Helper::checkLogin();

        if (!in_array($status, [2, 3])) {
            Helper::reponse('错误！非法参数。', 4002);
        }
        $orders = $this->db->select("tickets", '*', [
            "uid" => $uid,
            "status" => $status,
        ]);

        $msgArr = [
            '2' => '未完成',
            '3' => '已完成',
        ];
        Helper::reponse($msgArr[$status] . '订单', 200, $orders);
    }

    /**
     * 用户取消订单/系统取消超时订单：状态更新为“已售出”
     * 支付完成之后的回调：状态更新为“已售出”
     *
     * @param  [string]  $uid
     * @param  [string]  $ticketIds   逗号分隔
     * @param  [integer] $doStatus    3/1
     * @param  [string]  $credentials 支付凭证，用于确定是合法操作
     * @return [json]                 执行结果
     */
    public function order($uid = '', $ticketIds = '', $doStatus = 3, $credentials = '')
    {
        Helper::checkLogin();

        if (!$ticketIds) {
            return;
        }

        // 如果是支付完成回调，则需确定是合法操作
        if ($doStatus == 3) {
            Helper::isValidPay($credentials);
        }

        // 更新数据
        $updateData = [
            'status' => $doStatus,
            'time' => date('Y-m-d H:i:s'),
        ];
        if ($doStatus == 1) {
            $updateData['uid'] = 0;
        }

        // 条件
        $ticketIdsArr = explode(',', $ticketIds);
        $condition = [
            "id" => $ticketIdsArr,
            'status' => 2,
        ];
        if ($uid != 'V1Il7DrRAhOwMeXuUJxNK6HFjb9k4Q8m') {
            $condition['uid'] = $uid;
        }

        $res = $this->db->update("tickets", $updateData, $condition);
        Helper::reponse('成功更新' . $res->rowCount() . '条记录！');
    }
}
