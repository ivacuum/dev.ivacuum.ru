CREATE TABLE IF NOT EXISTS `site_comments` (
  `comm_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `page_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `user_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `minor_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `comm_time` int(11) NOT NULL DEFAULT '0',
  `comm_text` text COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`comm_id`),
  KEY `comm_page` (`comm_time`),
  KEY `page_id` (`page_id`),
  KEY `user_id` (`user_id`),
  KEY `minor_id` (`minor_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `site_downloads` (
  `dl_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `file_id` mediumint(8) unsigned NOT NULL,
  `dl_time` int(11) unsigned NOT NULL,
  `dl_ip` varchar(40) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`dl_id`),
  KEY `dl_time` (`dl_time`),
  KEY `dl_file_id` (`file_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `site_files` (
  `file_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `file_project` varchar(30) COLLATE utf8_bin NOT NULL,
  `file_folder` varchar(50) COLLATE utf8_bin NOT NULL,
  `file_time` int(11) unsigned NOT NULL,
  `file_name` varchar(255) COLLATE utf8_bin NOT NULL,
  `file_url` varchar(100) COLLATE utf8_bin NOT NULL,
  `file_size` bigint(15) unsigned NOT NULL DEFAULT '0',
  `file_extension` varchar(25) COLLATE utf8_bin NOT NULL,
  `download_count` mediumint(8) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`file_id`),
  KEY `file_project` (`file_project`,`file_folder`,`file_url`),
  KEY `download_count` (`download_count`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin ROW_FORMAT=COMPACT AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `site_images` (
  `image_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `album_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `image_url` varchar(25) COLLATE utf8_bin NOT NULL,
  `image_date` mediumint(6) unsigned zerofill NOT NULL DEFAULT '000000',
  `image_time` int(11) unsigned NOT NULL DEFAULT '0',
  `image_size` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `image_views` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `image_touch` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`image_id`),
  KEY `image_date` (`image_date`,`image_url`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `site_image_albums` (
  `album_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `album_title` varchar(255) COLLATE utf8_bin NOT NULL,
  `album_sort` smallint(5) unsigned NOT NULL,
  `album_images` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY (`album_id`),
  KEY `album_sort` (`album_sort`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin ROW_FORMAT=COMPACT AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `site_image_refs` (
  `ref_domain` varchar(255) COLLATE utf8_bin NOT NULL,
  `ref_views` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ref_domain`),
  KEY `ref_views` (`ref_views`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE IF NOT EXISTS `site_image_views` (
  `views_from` varchar(255) COLLATE utf8_bin NOT NULL,
  `views_count` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`views_from`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE IF NOT EXISTS `site_image_watermarks` (
  `wm_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `wm_title` varchar(255) COLLATE utf8_bin NOT NULL,
  `wm_file` varchar(255) COLLATE utf8_bin NOT NULL,
  `wm_width` smallint(4) unsigned NOT NULL DEFAULT '0',
  `wm_height` smallint(4) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`wm_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `site_quotes` (
  `quote_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `quote_votes` mediumint(8) NOT NULL DEFAULT '0',
  `quote_approver_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `quote_approver_name` varchar(255) COLLATE utf8_bin NOT NULL,
  `quote_approver_time` int(11) unsigned NOT NULL DEFAULT '0',
  `quote_sender_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `quote_sender_name` varchar(255) COLLATE utf8_bin NOT NULL,
  `quote_sender_time` int(11) unsigned NOT NULL DEFAULT '0',
  `quote_text` mediumtext COLLATE utf8_bin NOT NULL,
  `quote_comments` mediumint(8) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`quote_id`),
  KEY `quote_approver_time` (`quote_approver_time`),
  KEY `quote_sender_time` (`quote_sender_time`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `site_quotes_votes` (
  `vote_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `user_ip` varchar(40) COLLATE utf8_bin NOT NULL,
  `quote_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `vote_option` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `vote_time` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`vote_id`),
  KEY `user_id` (`user_id`),
  KEY `user_ip` (`user_ip`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin ROW_FORMAT=COMPACT AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `site_ranks` (
  `rank_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `rank_title` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `rank_min` mediumint(8) NOT NULL DEFAULT '0',
  `rank_special` tinyint(1) NOT NULL DEFAULT '0',
  `rank_image` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  PRIMARY KEY (`rank_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `site_smilies` (
  `smile_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `smile_code` varchar(50) COLLATE utf8_bin NOT NULL,
  `smile_title` varchar(50) COLLATE utf8_bin NOT NULL,
  `smile_image` varchar(50) COLLATE utf8_bin NOT NULL,
  `smile_height` smallint(3) unsigned NOT NULL DEFAULT '0',
  `smile_width` smallint(3) unsigned NOT NULL DEFAULT '0',
  `smile_sort` mediumint(5) unsigned NOT NULL DEFAULT '0',
  `smile_show` tinyint(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`smile_id`),
  KEY `smile_show` (`smile_show`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;
