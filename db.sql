create table test.tickets
(
	id int(11) unsigned auto_increment
		primary key,
	ticket char(7) default '' not null comment '如D11-100',
	status tinyint(1) unsigned default 1 not null comment '1：出售中，2：锁定中（未付款），3：已售出',
	uid int unsigned default 0 not null comment '用户id',
	time datetime not null comment '操作（锁定/售出）时间'
)
collate=utf8mb4_unicode_ci;

create index ind_uid
	on test.tickets (uid);

create index inx_ticket
	on test.tickets (ticket);