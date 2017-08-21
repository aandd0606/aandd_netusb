CREATE TABLE `cate` (
`cate_sn` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_sn` mediumint(8) unsigned NOT NULL,
  `cate_psn` int(10) unsigned NOT NULL,
  `cate_name` varchar(255) NOT NULL,
  PRIMARY KEY (`cate_sn`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE `files_center` (
`files_sn` smallint(5) unsigned NOT NULL AUTO_INCREMENT COMMENT '檔案流水號',
  `cate_name` varchar(255) NOT NULL COMMENT '欄位名稱',
  `cate_sn` smallint(5) unsigned NOT NULL COMMENT '欄位編號',
  `sort` smallint(5) unsigned NOT NULL COMMENT '排序',
  `kind` enum('img','file') NOT NULL COMMENT '檔案種類',
  `file_name` varchar(255) NOT NULL COMMENT '檔案名稱',
  `file_type` varchar(255) NOT NULL COMMENT '檔案類型',
  `file_size` int(10) unsigned NOT NULL COMMENT '檔案大小',
  `description` text NOT NULL COMMENT '檔案說明',
  `counter` mediumint(8) unsigned NOT NULL COMMENT '下載人次',
  PRIMARY KEY (`files_sn`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='檔案資料表' AUTO_INCREMENT=1 ;

CREATE TABLE `user` (
`user_sn` mediumint(9) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `mail` varchar(255) NOT NULL,
  `times` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY (`user_sn`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;