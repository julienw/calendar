-- phpMyAdmin SQL Dump
-- version 2.6.0-pl3
-- http://www.phpmyadmin.net
-- 
-- Serveur: sql2.lo-data.net
-- Généré le : Dimanche 08 Avril 2007 à 13:48
-- Version du serveur: 4.0.24
-- Version de PHP: 4.3.10-16
-- 
-- Base de données: `everlong`
-- 

-- --------------------------------------------------------

-- 
-- Structure de la table `cal_calendars`
-- 

CREATE TABLE `cal_calendars` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `id_owner` int(10) unsigned NOT NULL default '1',
  `name` varchar(50) NOT NULL default '',
  `rights_users` smallint(5) unsigned NOT NULL default '0',
  `rights_subscribed` smallint(5) unsigned NOT NULL default '0',
  `rights_guests` smallint(5) unsigned NOT NULL default '0',
  `rss_last` varchar(50) NOT NULL default '',
  `rss_next` varchar(50) NOT NULL default '',
  `rss_next_user` varchar(50) NOT NULL default '',
  `new_event_label` varchar(100) NOT NULL default 'Nouvel Ã©vÃ©nement',
  `active` tinyint(3) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

-- --------------------------------------------------------

-- 
-- Structure de la table `cal_calendars_users`
-- 

CREATE TABLE `cal_calendars_users` (
  `id_cal` int(11) unsigned NOT NULL default '0',
  `id_user` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id_cal`,`id_user`),
  KEY `id_user` (`id_user`)
) TYPE=MyISAM;

-- --------------------------------------------------------

-- 
-- Structure de la table `cal_events`
-- 

CREATE TABLE `cal_events` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `id_cal` int(11) NOT NULL default '0',
  `event` varchar(255) NOT NULL default '',
  `jour` date NOT NULL default '0000-00-00',
  `horaire` time default NULL,
  `id_submitter` int(11) default NULL,
  `submit_timestamp` timestamp(14) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `id_cal` (`id_cal`),
  KEY `jour` (`jour`)
) TYPE=MyISAM;

-- --------------------------------------------------------

-- 
-- Structure de la table `cal_events_users`
-- 

CREATE TABLE `cal_events_users` (
  `id_event` int(10) unsigned NOT NULL default '0',
  `id_user` int(10) unsigned NOT NULL default '0',
  `sure` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`id_event`,`id_user`),
  KEY `id_user` (`id_user`)
) TYPE=MyISAM;

-- --------------------------------------------------------

-- 
-- Structure de la table `cal_log`
-- 

CREATE TABLE `cal_log` (
  `id` int(11) NOT NULL auto_increment,
  `logtime` timestamp(14) NOT NULL,
  `ident` varchar(16) NOT NULL default '',
  `priority` int(11) NOT NULL default '0',
  `message` varchar(200) default NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

-- --------------------------------------------------------

-- 
-- Structure de la table `cal_users`
-- 

CREATE TABLE `cal_users` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `username` varchar(20) NOT NULL default '',
  `passwd` varchar(40) binary NOT NULL default '',
  `email` varchar(250) NOT NULL default '',
  `confirmcookie` varchar(20) binary default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `email` (`email`)
) TYPE=MyISAM;
