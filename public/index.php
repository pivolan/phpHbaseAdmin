<?php

error_reporting(E_ALL);
$root = dirname(dirname(__FILE__));

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
if (!defined('DL')) {
	define('DL', '_');
}

defined('APPLICATION_PATH')
    || define('APPLICATION_PATH',
              realpath(dirname(__FILE__) . '/../application'));

defined('APPLICATION_ROOT')
    || define('APPLICATION_ROOT',
              realpath(dirname(__FILE__) . '/../'));
              
              
# Set the include path to use your ZF app
set_include_path(
    $root . DS .'library'. PATH_SEPARATOR  # Here must be the Zend Framework Library
    . $root . DS .'application'. PATH_SEPARATOR
    . $root . DS .'application'. DS .'models'. PATH_SEPARATOR
    . get_include_path()
);
# Set environment
define('APPLICATION_ENV', 'production');

# Set thrift root for HBase and Scribe
$GLOBALS['THRIFT_ROOT'] = $root . DS .'library' . DS . 'Thrift';

setlocale(LC_TIME, 'ru_RU');

require 'Zend/Application.php';
$application = new Zend_Application(
	APPLICATION_ENV,
	APPLICATION_PATH . '/configs/application.ini'
);

$application->bootstrap()
	->run();


