# ticket
PHP订票小应用，根据看台设置出票，适用于演唱会或其他活动出票

![架构](./architecture.png)

## 系统介绍
### 背景
看台分为由过道隔开的ABCD共4个扇形区，

每区第一排座位数为50，往后逐排增加2个座位，最后一排100个座位。
### 功能
- 初始化座位票
- 查询余票
- 购票
- 查询“未完成”/“已完成”订单
- 取消订单
- 系统取消超时订单
### 用户体验
- 尽量让用户买到座位相邻的票
- 座位不相邻原因：
    - 自然不相邻：跨区/跨行
    - 夹在中间的票已经出售
- “随机出票”会影响座位相邻
    - 如果要求随机出票，那么就会导致下一次出票有可能不相邻，除非每次都从剩余票的第一张开始出票

## 开发
### 接口错误码定义
    错误码	语义                  举例
    4001	允许的用户不合理操作。   购票超过5张。
    4002	内部调试错误码。	     生成初始数据时，不是空表。
    4003	用户非法操作。         未登录。
    4004	业务相关的错误码。	     座位不相邻（不在同一区/排），需要用户确认后继续操作。
    5001	系统异常。	     出票失败。
    
## 安装
### 环境要求
- PHP >= 7.0
- composer
### 依赖
Medoo
### 开始
- composer install
- 执行db.sql文件建表，即可运行
