CREATE TABLE `llx_icomm_product` (
`rowid` int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
`fk_product` int(11) unsigned NOT NULL ,
`fk_user` int(11) unsigned NOT NULL ,
`date` int(10) unsigned,
`qty` double DEFAULT NULL,
`total` double(24,8) DEFAULT '0.00000000',
`fk_type` int(11) unsigned NOT NULL
) DEFAULT CHARSET=utf8;
