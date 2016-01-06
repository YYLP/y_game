-- phpMyAdmin SQL Dump
-- version 3.4.5
-- http://www.phpmyadmin.net
--
-- 主机: localhost
-- 生成日期: 2015 年 01 月 09 日 17:55
-- 服务器版本: 5.5.16
-- PHP 版本: 5.3.8

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- 数据库: `y_game_server_db`
--

-- --------------------------------------------------------

--
-- 表的结构 `friend_list`
--

CREATE TABLE IF NOT EXISTS `friend_list` (
  `n_id` int(11) NOT NULL AUTO_INCREMENT,
  `n_user_id` int(11) NOT NULL COMMENT '用户id',
  `n_friend_id` int(11) NOT NULL COMMENT '好友id',
  `t_create_time` datetime NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`n_id`),
  KEY `n_user_id` (`n_user_id`),
  KEY `n_friend_id` (`n_friend_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `mail_friend`
--

CREATE TABLE IF NOT EXISTS `mail_friend` (
  `n_id` int(11) NOT NULL AUTO_INCREMENT,
  `n_send_id` int(11) NOT NULL COMMENT '发送方id',
  `n_receive_id` int(11) NOT NULL COMMENT '接收方id',
  `n_type` int(11) NOT NULL COMMENT '邮件状态（1：请求好友；2：文字；0：已读）',
  `t_update_time` datetime NOT NULL COMMENT '更新时间',
  `t_create_time` datetime NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`n_id`),
  KEY `n_send_id` (`n_send_id`),
  KEY `n_receive_id` (`n_receive_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `mail_system`
--

CREATE TABLE IF NOT EXISTS `mail_system` (
  `n_id` int(11) NOT NULL AUTO_INCREMENT,
  `n_send_id` int(11) NOT NULL COMMENT '发送方id（系统为0）',
  `n_receive_id` int(11) NOT NULL COMMENT '接收方id',
  `s_message` blob NOT NULL COMMENT '邮件文字内容',
  `n_item_type` int(11) NOT NULL COMMENT '发送道具类型',
  `n_item_num` int(11) NOT NULL COMMENT '发送道具数值',
  `n_type` int(11) NOT NULL COMMENT '邮件状态',
  `t_update_time` datetime NOT NULL COMMENT '更新时间',
  `t_create_time` datetime NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`n_id`),
  KEY `n_receive_id` (`n_receive_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `regist_list`
--

CREATE TABLE IF NOT EXISTS `regist_list` (
  `n_id` int(11) NOT NULL AUTO_INCREMENT,
  `s_account` varchar(60) NOT NULL COMMENT '账号',
  `s_password` varchar(60) NOT NULL COMMENT '密码',
  `n_con_day` int(11) NOT NULL COMMENT '连续登录天数',
  `n_total_day` int(11) NOT NULL COMMENT '累计登陆天数',
  `n_login_time` int(11) NOT NULL COMMENT '登陆时间',
  `t_create_time` datetime NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`n_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=12 ;

-- --------------------------------------------------------

--
-- 表的结构 `user_boss_battle`
--

CREATE TABLE IF NOT EXISTS `user_boss_battle` (
  `n_id` int(11) NOT NULL AUTO_INCREMENT,
  `n_user_id` int(11) NOT NULL COMMENT '用户id',
  `n_hurt` int(11) NOT NULL COMMENT '最大伤害值',
  `t_update_time` datetime NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`n_id`),
  KEY `n_user_id` (`n_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `user_info`
--

CREATE TABLE IF NOT EXISTS `user_info` (
  `n_id` int(11) NOT NULL COMMENT '用户id',
  `s_name` char(20) NOT NULL COMMENT '用户昵称',
  `n_sex` int(11) NOT NULL COMMENT '性别（0：女；1：男）',
  `n_head` int(11) NOT NULL COMMENT '头像id',
  `n_coin` int(11) NOT NULL COMMENT '金币',
  `n_diamond` int(11) NOT NULL COMMENT '钻石',
  `n_soul` int(11) NOT NULL COMMENT '魂石数',
  `f_experience` float NOT NULL COMMENT '经验值',
  `n_level` int(11) NOT NULL COMMENT '等级',
  `n_battle` int(11) NOT NULL COMMENT '战斗力',
  `n_max_checkpoint` int(11) NOT NULL COMMENT '最大关卡数',
  `s_checkpoint_info` text NOT NULL COMMENT '关卡信息',
  `s_role_info` text NOT NULL COMMENT '角色信息',
  `s_general_info` text NOT NULL COMMENT '武将信息',
  `s_item_info` text NOT NULL COMMENT '道具信息',
  `s_task_info` text NOT NULL COMMENT '任务信息',
  `s_achievement_info` text NOT NULL COMMENT '成就信息',
  `t_create_time` datetime NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`n_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- 限制导出的表
--

--
-- 限制表 `friend_list`
--
ALTER TABLE `friend_list`
  ADD CONSTRAINT `friend_list_ibfk_1` FOREIGN KEY (`n_user_id`) REFERENCES `user_info` (`n_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `friend_list_ibfk_2` FOREIGN KEY (`n_friend_id`) REFERENCES `user_info` (`n_id`) ON DELETE CASCADE;

--
-- 限制表 `mail_friend`
--
ALTER TABLE `mail_friend`
  ADD CONSTRAINT `mail_friend_ibfk_1` FOREIGN KEY (`n_send_id`) REFERENCES `user_info` (`n_id`),
  ADD CONSTRAINT `mail_friend_ibfk_2` FOREIGN KEY (`n_receive_id`) REFERENCES `user_info` (`n_id`);

--
-- 限制表 `mail_system`
--
ALTER TABLE `mail_system`
  ADD CONSTRAINT `mail_system_ibfk_1` FOREIGN KEY (`n_receive_id`) REFERENCES `user_info` (`n_id`);

--
-- 限制表 `user_boss_battle`
--
ALTER TABLE `user_boss_battle`
  ADD CONSTRAINT `user_boss_battle_ibfk_1` FOREIGN KEY (`n_user_id`) REFERENCES `user_info` (`n_id`) ON DELETE CASCADE;

--
-- 限制表 `user_info`
--
ALTER TABLE `user_info`
  ADD CONSTRAINT `user_info_ibfk_1` FOREIGN KEY (`n_id`) REFERENCES `regist_list` (`n_id`) ON DELETE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
