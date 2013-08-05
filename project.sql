-- phpMyAdmin SQL Dump
-- version 2.10.3
-- http://www.phpmyadmin.net
-- 
-- 主機: localhost
-- 建立日期: Apr 22, 2011, 07:56 AM
-- 伺服器版本: 5.0.51
-- PHP 版本: 5.2.6

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

-- 
-- 資料庫: `project`
-- 

-- --------------------------------------------------------

-- 
-- 資料表格式： `book`
-- 

CREATE TABLE `book` (
  `filehandle` int(10) unsigned NOT NULL auto_increment,
  `author` char(20) NOT NULL,
  `filename` varchar(32) character set utf8 collate utf8_unicode_ci default NULL,
  `desc` varchar(100) character set utf8 collate utf8_unicode_ci default NULL,
  `createtime` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `modifiedtime` timestamp NOT NULL default '0000-00-00 00:00:00',
  `mode` tinyint(6) unsigned NOT NULL,
  `pages` tinyint(5) NOT NULL default '0',
  PRIMARY KEY  (`filehandle`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=63 ;

-- 
-- 列出以下資料庫的數據： `book`
-- 

INSERT INTO `book` VALUES (49, 'zoo', '222', '', '2010-12-15 05:19:49', '2010-12-15 05:19:49', 0, 0);
INSERT INTO `book` VALUES (54, 'zoo', '1312312312', '', '2010-12-27 22:32:23', '2010-12-27 22:36:15', 0, 0);
INSERT INTO `book` VALUES (51, 'zoo', '12321', '', '2010-12-16 00:07:21', '2010-12-16 00:07:21', 0, 0);
INSERT INTO `book` VALUES (48, 'zoo', '999', '', '2010-12-15 04:50:58', '2010-12-15 04:50:58', 0, 0);
INSERT INTO `book` VALUES (55, 'zoo', '321', '', '2011-01-11 02:08:49', '2011-01-11 02:09:06', 0, 0);
INSERT INTO `book` VALUES (56, 'zoo', '555', '', '2011-01-24 22:37:59', '2011-01-24 22:40:37', 0, 0);
INSERT INTO `book` VALUES (57, 'zoo', 'aadd', '', '2011-02-21 10:15:24', '2011-02-21 10:15:24', 0, 0);
INSERT INTO `book` VALUES (58, 'zoo', 'xcv', '', '2011-02-21 11:18:06', '2011-02-21 11:18:11', 0, 1);
INSERT INTO `book` VALUES (59, 'zoo', 'adasd', '', '2011-02-21 11:36:26', '2011-03-09 18:35:28', 0, 3);
INSERT INTO `book` VALUES (60, 'zoo', '123123', '', '2011-03-07 17:51:16', '2011-03-07 17:51:16', 0, 0);
INSERT INTO `book` VALUES (61, 'zoo', '321321', '', '2011-03-10 17:54:52', '2011-03-10 17:54:52', 0, 0);
INSERT INTO `book` VALUES (62, 'zoo', '31143123123', '', '2011-03-10 18:11:18', '2011-03-10 18:18:40', 0, 0);

-- --------------------------------------------------------

-- 
-- 資料表格式： `member`
-- 

CREATE TABLE `member` (
  `no` int(6) NOT NULL auto_increment,
  `username` char(20) NOT NULL,
  `password` varchar(32) NOT NULL,
  `fbtoken` varchar(100) NOT NULL,
  `space_limit` int(50) NOT NULL default '0',
  `space_free` int(50) NOT NULL default '0',
  `space_used` int(50) NOT NULL default '0',
  PRIMARY KEY  (`no`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

-- 
-- 列出以下資料庫的數據： `member`
-- 

INSERT INTO `member` VALUES (1, 'zoo', '81dc9bdb52d04dc20036dbd8313ed055', '166506083896|2.8ccYGOdAv7O6h0_6DvOR0g__.3600.1301569200-597020862|GL3C9E4s-q5J6pRva58PF9JQipc', 10240, 9949, 17);
INSERT INTO `member` VALUES (2, 'xxx', 'b59c67bf196a4758191e42f76670ceba', '', 0, 0, 0);

-- --------------------------------------------------------

-- 
-- 資料表格式： `picture`
-- 

CREATE TABLE `picture` (
  `pid` int(10) unsigned NOT NULL auto_increment,
  `owner` int(10) NOT NULL,
  `url` varchar(50) NOT NULL,
  `title` varchar(32) NOT NULL,
  `tag` varchar(32) NOT NULL,
  `createtime` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `size` int(10) NOT NULL,
  PRIMARY KEY  (`pid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=181 ;

-- 
-- 列出以下資料庫的數據： `picture`
-- 

INSERT INTO `picture` VALUES (177, 1, './picture/1/bc3afc2ea6373b17a2b57346588c6792', '櫻花.jpg', '000', '2011-04-21 17:38:51', 6703);
INSERT INTO `picture` VALUES (178, 1, './picture/1/a5e6db3d093819fed54676690bd9b633', '企鵝.jpg', '000', '2011-04-21 17:39:31', 2490);
INSERT INTO `picture` VALUES (179, 1, './picture/1/767df611d59123935b06ae97e6f9f244', '武士刀.jpg', '000', '2011-04-21 17:44:31', 4109);
INSERT INTO `picture` VALUES (180, 1, './picture/1/34d671d0be8a93ae4788156d1ca76382', '海豚.jpg', '000', '2011-04-21 17:45:33', 4499);
