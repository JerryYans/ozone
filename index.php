<?php
if (!defined('E_DEPRECATED')) {
    define('E_DEPRECATED',0);
}
error_reporting(E_ALL&~E_DEPRECATED^E_NOTICE^E_WARNING);

$base_uri = DIRECTORY_SEPARATOR=='/'?dirname($_SERVER["SCRIPT_NAME"]):str_replace('\\','/',dirname($_SERVER["SCRIPT_NAME"]));

define("BASE_URI",$base_uri=='/'?'':$base_uri);

unset($base_uri);

define('APP_NAME','ozone');

define('APP_PATH',realpath(dirname(__FILE__)).'/');

define('SYS_PATH',APP_PATH."system/");

$G_LOAD_PATH=array(
        APP_PATH,
);
$G_CONF_PATH=array(
        APP_PATH."../config/".APP_NAME."/",
);


require_once (SYS_PATH."functions.php");

cpf_require_class("CPF");
// APF::get_instance()->set_request_class('AJKRequest');
// if (apf_require_class("AJKResponse"))
//     APF::get_instance()->set_response_class('AJKResponse');
CPF::get_instance()->run();


