<?php
$conf['db_host'] = getenv('MYSQLHOST') . ':' . getenv('MYSQLPORT');
$conf['db_user'] = getenv('MYSQLUSER');
$conf['db_password'] = getenv('MYSQLPASSWORD');
$conf['db_base'] = getenv('MYSQLDATABASE');
$conf['db_prefix'] = 'piwigo_';

// DÒNG QUAN TRỌNG NHẤT: Báo hiệu đã cài đặt xong
define('PHPWG_INSTALLED', true);
?>
