<?php


class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
	protected $_appNamespace = 'App';

	function _initHBase()
	{
		$cfg = $this->getOption('hbase_thrift');
		HBase_Table::init(
			$cfg['host'],
			$cfg['port'],
			$cfg['sendTimeout'],
			$cfg['recvTimeout']
		);
	}

	function _initMemcached()
	{
		$cfg = $this->getOption('memcached');
		App_Memcached::init(
			$cfg['pid'],
			$cfg['servers'],
			$cfg['options']
		);
	}

	function _initRoutes()
	{
		$routes = $this->getOption('routes');

		$this->bootstrap("frontController");

		$config = new Zend_Config_Ini(APPLICATION_PATH . DS . 'configs' . DS . 'routes.ini', $routes['section']);
		$router = new Zend_Controller_Router_Rewrite();
		$router->addConfig($config, 'routes');

		$frontController = Zend_Controller_Front::getInstance();
		$frontController->setRouter($router);

		if (isset($routes['block_modules'])) {
			App_Controller_Plugin_ModuleBlock::setModulesToBlock($routes['block_modules']);
		}
	}

	protected function _initAutoload()
	{
		$moduleLoader = new Zend_Application_Module_Autoloader(array('namespace' => '', 'basePath'  => APPLICATION_PATH));
		return $moduleLoader;
	}
}
