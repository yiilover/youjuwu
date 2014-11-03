alter table pre_forum_thread add `sex` int(1) NOT NULL DEFAULT '0';
alter table pre_forum_thread add `age` varchar(10) NOT NULL DEFAULT '';
alter table pre_forum_thread add `qq` varchar(20) NOT NULL DEFAULT '';
alter table pre_forum_thread add `mob` varchar(20) NOT NULL DEFAULT '';

alter table pre_forum_thread drop column `sex`;
alter table pre_forum_thread drop column `age`;
alter table pre_forum_thread drop column `qq`;
alter table pre_forum_thread drop column `mobile`;


alter table pre_forum_post add `sex` int(1) NOT NULL DEFAULT '0';
alter table pre_forum_post add `age` varchar(10) NOT NULL DEFAULT '';
alter table pre_forum_post add `qq` varchar(20) NOT NULL DEFAULT '';
alter table pre_forum_post add `mob` varchar(20) NOT NULL DEFAULT '';

