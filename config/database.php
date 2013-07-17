<?php
$default_fetch_mode = PDO::FETCH_ASSOC;

// read/write
$config['master'] = array (
		"dsn"=>"mysql:host=SAE_MYSQL_HOST_M;dbname=SAE_MYSQL_DB",

		'username' => SAE_MYSQL_USER,
		'password' => SAE_MYSQL_PASS,

		'init_attributes' => array(),
		'init_statements' => array('SET CHARACTER SET utf8','SET NAMES utf8'),

		'default_fetch_mode' => $default_fetch_mode
);

$config['slave'] = array (

		"dsn"=>"mysql:host=SAE_MYSQL_HOST_S;dbname=SAE_MYSQL_DB",

		'username' => SAE_MYSQL_USER,
		'password' => SAE_MYSQL_PASS,

		'init_attributes' => array(),
		'init_statements' => array('SET CHARACTER SET utf8','SET NAMES utf8'),

		'default_fetch_mode' => $default_fetch_mode
);