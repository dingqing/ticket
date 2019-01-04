<?php
require 'vendor/autoload.php';

use Medoo\Medoo;
use Ticket\Ticket;

spl_autoload_register('autoload');

function autoload($class)
{
    $classArr = explode('\\', $class);
    $className = array_pop($classArr);
    $space = implode('/', $classArr);
    // namespace to lower case
    $space = strtolower($space);

    include  __DIR__. DIRECTORY_SEPARATOR.$space . '/' . $className . '.php';
}

$db = new Medoo([
    'database_type' => 'mysql',
    'database_name' => 'test',
    'server'        => 'localhost',
    'username'      => 'root',
    'password'      => '',
    'charset'       => 'utf8'
]);
$TicketService = new Ticket($db);

// 初始化：生成所有座位票
// $TicketService->initTickets();

// 查询余票
$TicketService->inquireTickets();
// 购票
// $TicketService->buyTickets(2, 5);

// 用户查询“未完成”/“已完成”订单
// $TicketService->userOrder(2, 2);
// 用户取消订单
// $TicketService->order(2, '3243,3', 1);
// 付款成功回调：状态更新为“已售出”
//$TicketService->order('2', '3242,3', 3, 'some-credentials');

// 系统取消超时订单
// $TicketService->order('V1Il7DrRAhOwMeXuUJxNK6HFjb9k4Q8m', '3242,3', 1);
