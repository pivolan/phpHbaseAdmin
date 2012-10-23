<?php
/**
 * Модель для работы с админкой
 * User: pivo
 * Date: 06.01.2011
 * Time: 15:37:17
 */

/**
 */
class Model_Admins extends HBase_Table
{
	protected static $_table = 'video';
	protected $data = array();
	public $id = Null;

	public static function setTable($table)
	{
		self::$_table = $table;
	}

	public static function getTable()
	{
		return self::$_table;
	}

	public function __construct()
	{
		$methods = get_class_vars(get_class($this));
		foreach ($methods as $key=> $value) {
			echo $key . '<br>';
			if (strstr($key, 'CF_')) {
				unset($this->$key);
			}
		}

	}
}