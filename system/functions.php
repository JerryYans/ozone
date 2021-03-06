<?php

//调试打印信息
function dump($data, $is_exit=true){
	echo "<pre>";
	print_r($data);
	echo "</pre>";
	if ($is_exit){
		exit;
	}
}

function cpf_error_handler($errno, $errstr, $errfile, $errline) {
    if (($errno & error_reporting()) != 0) {

        $level_names = array(
        E_ERROR => 'E_ERROR', E_WARNING => 'E_WARNING',
        E_PARSE => 'E_PARSE', E_NOTICE => 'E_NOTICE',
        E_CORE_ERROR => 'E_CORE_ERROR',
        E_CORE_WARNING => 'E_CORE_WARNING',
        E_COMPILE_ERROR => 'E_COMPILE_ERROR',
        E_COMPILE_WARNING => 'E_COMPILE_WARNING',
        E_USER_ERROR => 'E_USER_ERROR',
        E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE' );
        if (defined('E_STRICT')) {
            $level_names[E_STRICT]='E_STRICT';
        }
        $levels=array();
        $value=$errno;
        if (($value&E_ALL)==E_ALL) {
            $levels[]='E_ALL';
            $value&=~E_ALL;
        }
        foreach($level_names as $level=>$name) {
            if(($value&$level)==$level) $levels[]=$name;
        }

        /*
        ob_start();
        debug_print_backtrace();
        $trace = ob_get_contents();
        ob_end_clean();
        $trace = preg_replace('/[\r\n]/', '; ', $trace);
        */

        $a = @debug_backtrace(false);

        $trace = format_trace($a);
        $apf = APF::get_instance();
        if ($apf->get_config('display_error')) {
            echo implode(' | ',$levels), " ". $errstr, ", TRACE: " ,$trace;
        }
        $logger = $apf->get_logger();
        $logger->warn("error_handler",
        implode(' | ',$levels), " ". $errstr, ", TRACE: " ,$trace);
    }
    return TRUE;
}
/**
 * 记录程序异常
 * @param Exception $exception
 */
function cpf_exception_handler($exception) {
    $logger = APF::get_instance()->get_logger();
    $trace = format_trace($exception->getTrace());
    $trace = "exception '"
    . get_class($exception) . "' with message '"
    . $exception->getMessage()."' in "
    . $exception->getFile() . ":"
    . $exception->getLine() . " Stack trace: " . $trace;
    $logger->warn("cpf_exception_handler", $trace);
    return TRUE;
}

/**
 * 格式化debug_backtrace
 * @param $trace
 */
function format_trace($trace) {
    if (!is_array($trace)) {
        return;
    }
    //出错时的url
    $error_url = @$_SERVER['REQUEST_URI'];
    $http_host = @$_SERVER['HTTP_HOST'];
    $http_refer = @$_SERVER['HTTP_REFERER'];
    $trace_str = "error_url : {$http_host}{$error_url} refer : $http_refer";
    foreach ($trace as $key=>$val) {
        $trace_str .= "#{$key} ".@$val['file']." (".@$val['line'].") : ";
        if (isset($val['class'])) {
            $trace_str .= "{$val['class']}{$val['type']}";
        }

        $trace_str .= "{$val['function']}(";
        if (is_array(@$val['args'])) {
            foreach ($val['args'] as $v) {
                $_v = preg_replace('#[\r\n \t]+#',' ',print_r($v,true));
                $_v = substr($_v,0,100);
                $trace_str .= $_v .",";
            }
            $trace_str = rtrim($trace_str,',');
        }
        $trace_str .=") ";
    }
    return $trace_str;
}
/**
 * 导入文件，对php原生require_once的封装和扩展
 * @param string $file 文件名
 * @param string $prefix 上一级目录
 * @return boolean
 */
function cpf_require_file($file, $prefix="lib") {
    global $G_LOAD_PATH;
    if (defined('CACHE_PATH') && $prefix != "lib") {
        $f = cpf_class_to_cache_file($file,$prefix);
        if (file_exists($f)) {
            if (!cpf_required_files($file,$prefix)) {
                require_once "$f";
            }
            return true;
        }
    }
    foreach ($G_LOAD_PATH as $path) {
        if (file_exists("$path$prefix/$file")) {
            if (!defined('CACHE_PATH') || !cpf_required_files($file,$prefix)) {
                require_once("$path$prefix/$file");
                if (defined('CACHE_PATH') && $prefix != "lib") {
                    cpf_save_to_cache($file,$prefix,"$path$prefix/$file");
                }
            }
            return true;
        }
    }
    return false;
}
/**
 * 判断指定文件是否已经被载入（缓存）
 * @param string $file 文件名
 * @param string $prefix 上一级目录
 */
function cpf_required_files ($file,$prefix) {
    global $cached_files;
    $f = $prefix . "/" . $file;
    if (in_array($f,$cached_files)) {
        return true;
    } else {
        $cached_files[] = $f;
    }
    return false;
}
/**
 * 判断源文件是否被压缩
 * @param string $file 文件名
 * @param string $prefix 上一级目录
 */
function cpf_file_cache_exist ($file,$prefix) {
    $dest_file = cpf_class_to_cache_file($file,$prefix);
    if (file_exists($dest_file)) {
        return $dest_file;
    } else {
        return false;
    }
}
/**
 * 返回源文件对应的压缩文件路径
 * @param string $file 文件名
 * @param string $prefix 上一级目录
 */
function cpf_class_to_cache_file ($file,$prefix) {
    return CACHE_PATH . $prefix . "/" . $file;
}
/**
 * 去掉指定文件的空白、注释，然后存储到CACHE_PATH目录中
 * @param string $file 文件名
 * @param string $prefix 上一级目录
 * @param string $source 源文件名
 */
function cpf_save_to_cache ($file,$prefix,$source) {
    $dest_file = cpf_class_to_cache_file($file,$prefix);
    if (file_exists($dest_file)) {
        return ;
    }
    $dir = dirname($dest_file);
    if (!is_dir($dir)) {
        @mkdir($dir,0775,TRUE);
    }
    //$txt = @php_strip_whitespace($source);
    file_put_contents($dest_file,@php_strip_whitespace($source));
}
function import($class , $prefix="classes") {
    $file = cpf_classname_to_filename($class,'.');
    $flag = true;
    if (!cpf_require_file("$file.php", $prefix)) {
        if ($firelog) {
            $logger = APF::get_instance()->get_logger();
            //出错时的url
            $error_url = @$_SERVER['REQUEST_URI'];
            //屏蔽由于某些蜘蛛将url处理成小写时，引发的class not found
            if (preg_match('#\.js$|\.css$#', $error_url)) {
                return false;
            }
            $http_host = @$_SERVER['HTTP_HOST'];
            $http_refer = @$_SERVER['HTTP_REFERER'];
            $logger->error("'$prefix/$class' not found error_url : {$http_host}{$error_url} refer : $http_refer");
            //add by jackie for more error infomation
            ob_start();
            debug_print_backtrace();
            $trace = ob_get_contents();
            ob_end_clean();
            $logger->error($trace);
        }
        return false;
    }
    return $flag;
}
/**
 * 导入类
 * @param string $class 类名
 * @param string $prefix 父目录
 * @param string $firelog
 * @return boolean
 */
function cpf_require_class($class, $prefix="classes" , $firelog = true) {
    if($prefix=="classes" && class_exists($class)){
        return true;
    }
    $file = cpf_classname_to_filename($class);
    $flag = true;
    if(substr($class, 0, 3) == "HK_" && !cpf_require_file("$file.php", $prefix)) {
        $class = substr($class, 3);
        $file = cpf_classname_to_filename($class);
        $flag = false;
    }
    if (!cpf_require_file("$file.php", $prefix)) {
        if ($firelog) {
            $error_url = @$_SERVER['REQUEST_URI'];
            //屏蔽由于某些蜘蛛将url处理成小写时，引发的class not found
            if (preg_match('#\.js$|\.css$#', $error_url)) {
                return false;
            }
            trigger_error("'$prefix/$class' not found", E_USER_ERROR); //use trigger_error instead

        }
        return false;
    }
    return $flag;
}
/**
 * 导入v2控制器，cpf_require_class的简单封装。
 * @param string $class 类名
 * @param string $firelog 日志开关
 * @return boolean
 */
function cpf_require_controller($class, $firelog=true) {
    if(class_exists($class."Controller")){
        return true;
    }
    return cpf_require_class($class, "controller" , $firelog);
}
/**
 * 导入v2拦截器，cpf_require_class的简单封装。
 * @param string $class 类名
 * @return boolean
 */
function cpf_require_interceptor($class) {
    if(class_exists($class."Interceptor")){
        return true;
    }
    return cpf_require_class($class, "interceptor");
}
/**
 * 导入v2组件，cpf_require_class的简单封装。
 * @param string $class 类名
 * @return boolean
 */
function cpf_require_component($class) {
    if(class_exists($class."Component")){
        return true;
    }
    return cpf_require_class($class, "component");
}
/**
 * 导入v2页面，cpf_require_class的简单封装。
 * @param string $class 类名
 * @return boolean
 */
function cpf_require_page($class) {
    if(class_exists($class."Page")){
        return true;
    }
    return cpf_require_class($class, "page");
}

/**
 * 类名转换成文件路径
 * 例如V2b_Solr_Property则返回v2b/solr/
 * @param string $class Class name
 * @return string Relative path
 */
function cpf_classname_to_path($class) {
    $paths = @split("_", $class);
    $count = count($paths) - 1;
    $path = "";
    for ($i = 0; $i < $count; $i++) {
        $path .= strtolower($paths[$i]) . "/";
    }
    return $path;
}

/**
 * 类名转换成文件名（木有后缀）
 * 类名由下划线分割，最后一部分为类名，之前的为相对路径
 * 例如Solr_Property标志solr目录下的property
 * @param string $class Class name
 * @return string Relative path
 */
function cpf_classname_to_filename($class , $explode = '_') {
    $paths = explode($explode, $class);
    $count = count($paths) - 1;
    $path = "";
    for ($i = 0; $i < $count; $i++) {
        $path .= strtolower($paths[$i]) . "/";
    }
    $class = $paths[$count];
    return "$path$class";
}
/*
function __autoload ($classname) {
    $matches = array();
    if (preg_match('/(.+)controller$/i',$classname,$matches)) {
        cpf_require_class($matches[1],'controller');
    } else if (preg_match('/(.+)page$/i',$classname,$matches)) {
        cpf_require_class($matches[1],'page');
    } else  if (preg_match('/(.+)component$/i',$classname,$matches)) {
        cpf_require_class($matches[1],'component');
    } else  if (preg_match('/(.+)interceptor$/i',$classname,$matches)) {
        cpf_require_class($matches[1],'interceptor');
    } else {
        cpf_require_class($classname);
    }
}*/

