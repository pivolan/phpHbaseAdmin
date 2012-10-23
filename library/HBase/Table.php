<?php

class HBase_Table
{
	//Стандартные свойства используемые для создания column family d БД
	const BP_maxVersions = 'maxVersions';
	const BP_compression = 'compression';
	const BP_inMemory = 'inMemory';
	const BP_bloomFilterType = 'bloomFilterType';
	const BP_bloomFilterVectorSize = 'bloomFilterVectorSize';
	const BP_bloomFilterNbHashes = 'bloomFilterNbHashes';
	const BP_blockCacheEnabled = 'blockCacheEnabled';
	const BP_timeToLive = 'timeToLive';
	public static $BASIC_PROPERTIES = array(
		self::BP_maxVersions,
		self::BP_compression,
		self::BP_inMemory,
		self::BP_bloomFilterType,
		self::BP_bloomFilterVectorSize,
		self::BP_bloomFilterNbHashes,
		self::BP_blockCacheEnabled,
		self::BP_timeToLive
	);
	//Нестандартные свойства column-family, которые можно использовать при определении $_families
	/**
	 * Тип кеширования column family при использовании getCachedData и setCachedData
	 * Значение по умолчанию: CPT_SPECIAL
	 */
	const CP_TYPE = 'hbase_cache_type';
	const CP_GET_CACHE = 'hbase_get_cache_callback';
	const CP_GET_DB = 'hbase_get_db_callback';
	const CP_SET_CACHE = 'hbase_set_cache_callback';
	const CP_SET_DB = 'hbase_set_db_callback';
	//Порядок следования элементов массиве важен!!!
	public static $CUSTOM_PROPERTIES = array(
		self::CP_TYPE,
		self::CP_GET_CACHE,
		self::CP_GET_DB,
		self::CP_SET_CACHE,
		self::CP_SET_DB
	);
	//Типы кеширования column families
	/**
	 * Для column family, каждое значение в которых обладает собственным типом
	 * Все содержимое column family сохраняется в Memcached в виде ассоциативного массива по ключу, определяемому getCacheKey
	 */
	const CPT_OBJECT = 'object';
	/**
	 * Для column family, представляющих собой множество однотипных значений
	 * По ключу, определяемому getCacheKey, в Memcached записывается карта column family -
	 * индексный массив, который упорядочивает множество значений, хранящихся в ней.
	 * Каждый элемент карты содержит либо значение целиком, либо ключ Memcached, в котором оно хранится, либо часть значения
	 * и ключ в Memcached, где можно достать остальное.
	 */
	const CPT_ARRAY = 'array';
	/**
	 * Для особых column family, дл которых непринимы ранее описанные типы
	 * Column family такого типа нельзя получить используя getCachedData или записать используя setCachedData
	 */
	const CPT_SPECIAL = 'special';
	//Служебные нестандартные свойства column-family, не используются при описании column families
	const CP_NAME = 'name';//Не используе
	const CP_PARAMS = 'params';


	protected static $_table = null;
	protected static $_families = null;
 	/** @var $_connection HBase_Connection */
	private static $_connection = null;
	private static $isInitialized = false;

	public static function init($host, $port, $sendTimeout = 10000, $recvTimeout = 20000)
	{
		self::$_connection = new HBase_Connection($host, $port, $sendTimeout, $recvTimeout);
		self::$isInitialized = true;
	}

	public static function flush()
	{
		if (self::$isInitialized)
		{
			self::$_connection->flush();
		}
	}

	public static function disconnect($silent = false)
	{
		if (self::$isInitialized)
		{
			self::$_connection->close($silent);
		}
	}

	public function __destruct()
	{
		self::disconnect(true);
	}

	public static function install($preserveOldData = false)
	{
		$table = static::$_table;

		$columns = array();
		foreach (static::$_families as $columnFamily => $declaredProperties)
		{
			$properties = array();
			foreach (self::$BASIC_PROPERTIES as $name)
			{
				if (isset($declaredProperties[$name]))
				{
					$properties[$name] = $declaredProperties[$name];
				}
			}
			$columns[$columnFamily] = $properties;
		}
		if ($table && $columns)
		{
			$tables = self::$_connection->getTableNames();

			$tableExists = in_array($table, $tables);
			if ((!$preserveOldData) || (!$tableExists))
			{
				if ($tableExists)
				{
					self::delete($table);
				}
				self::create($table, $columns);
			}
			//self::disconnect();
		}
	}
	public static function checkColumnFamilies()
	{
		$table = static::$_table;
		$families = static::$_families;
		echo '<p>Checking table "'.$table.'"</p>';
		try
		{
			$descriptors = self::$_connection->getColumnDescriptors($table);
			if (is_array($families) && is_array($descriptors))
			{
				$normal = array_intersect_key($families, $descriptors);
				echo '<p>Column families declared in code, but not added to DB</p>';
				foreach ($families as $name => $props)
				{
					if (!isset($normal[$name]))
					{
						echo '<p style="color:red;">' . $name . '</p>';
					}
				}
				echo '<p>Column families not declared in code, but added to DB</p>';
				foreach ($descriptors as $name => $props)
				{
					if (!isset($normal[$name]))
					{
						echo '<p style="color:green;">' . $name . '</p>';
					}
				}
			}
		}
		catch (Exception $e)
		{
			echo '<p>Error occured:'.$e.'</p>';
		}
		
	}

	public static function getCacheKey($columnFamily, $rowId)
	{
		return static::$_table . '_' . $rowId . '_' . $columnFamily;
	}
	/**
	 * Достает данные из базы и формирует из них многоомерный ассоциативный массив
	 * @static
	 * @throws Exception
	 * @param  $rowId - идентификатор строки
	 * @param array $columnFamilies - запрашиваемые колонки и/или column families  
	 * @return array(<columnFamilyName> => array(<columnId> => array('value' => <value>, 'time' => <timestamp>)))
	 */
	public static function getData($rowId, $columnFamilies = array())
	{
		$result = null;

		if (!is_array($columnFamilies))
		{
			throw new Exception('$columnFamilies must be an array');
		}
		if (count($columnFamilies) <= 0)
		{
			$columnFamilies = array_keys(static::$_families);
		}
		$rows = self::getRowWithColumns($rowId, $columnFamilies);
		if (count($rows) > 0)
		{
			$columns = $rows[0]->columns;
			$result = array();
			foreach ($columns as $columnId => $value)
			{
				$columnIdParts = explode(':', $columnId);
				if (count($columnIdParts) == 2)
				{
					$columnFamilyName = $columnIdParts[0] . ':';
					if (!isset($result[$columnFamilyName]))
					{
						$result[$columnFamilyName] = array();
					}
					$result[$columnFamilyName][$columnIdParts[1]] = array(
						'time' => $value->timestamp,
						'value' => $value->value
					);
				}
			}
		}
		return $result;
	}
	/**
	 * Изменяет данные в БД в соотвествии с переданным ассоциативным многомерным массивом
	 * @static
	 * @throws Exception
	 * @param  $rowId - идентификатор строки
	 * @param  $columnFamilies - array(<columnFamilyName> => array(<columnId> => null | <value>))
	 * 		если передан null - колонка удаляется, если <value>, то оно сериализуется при необходимости и колонка либо создается либо изменяется
	 * @return void
	 */
	public static function setData($rowId, $columnFamilies)
	{
		if (!is_array($columnFamilies))
		{
			throw new Exception('$columnFamilies must be an array');
		}
		if (count($columnFamilies) <= 0)
		{
			throw new Exception('$columnFamilies must contain at least one column family name.');
		}

		$mutations = array();
		foreach ($columnFamilies as $columnFamilyName => $columns)
		{
			foreach ($columns as $columnId => $value)
			{
				if (is_null($value))
				{
					//Delete column
					$mutations[] = new Mutation(array(
						'column' => $columnFamilyName.$columnId,
						'isDelete' => true
					));
				}
				else
				{
					if (!is_string($value))
					{
						$value = Zend_Json::encode($value);
					}
					//Add or edit column
					$mutations[] = new Mutation(array(
						'column' => $columnFamilyName.$columnId,
						'value' => $value
					));
				}
			}
		}
		if (count($mutations) > 0)
		{
			self::mutateRow($rowId, $mutations);
		}
	}
	/**
	 * Обеспечивает доступ к данным запрошенных column-families указанного row, используя Memcached для ускорения доступа
	 * @static
	 * @param  $rowId - идентификатор row
	 * @param  $columnFamilies - array(<columnFamilyName> | <columnFamilyName> => <params>) - массив необходимых column-families
	 * @return array(<columnFamily> => <data>)
	 */
	public static function getCachedData($rowId, $columnFamilies = array())
	{
		$result = array();

		//Get column family properties
		$mainCacheKeys = array();
		$allProperties = array();
		self::parseColumnFamilies($rowId, $columnFamilies, $mainCacheKeys, $allProperties);

		//Get from cache
		$dataFromCache = null;
		$uncachedColumnFamilies = array();
		if (App_Memcached::getMulti($mainCacheKeys, $dataFromCache) && (is_array($dataFromCache)))
		{
			foreach($mainCacheKeys as $cacheKey)
			{
				$props = $allProperties[$cacheKey];
				if (isset($dataFromCache[$cacheKey]))
				{
					$data = $dataFromCache[$cacheKey];
					if (is_callable($props[self::CP_GET_CACHE]))
					{
						$dataToSet = call_user_func($props[self::CP_GET_CACHE], $rowId, $data, $props[self::CP_PARAMS]);
						if (isset($dataToSet['return']))
						{
							$result[$props[self::CP_NAME]] = $dataToSet['return'];
						}
					}
					else
					{
						throw new Exception('Invalid "'.self::CP_GET_CACHE.'" callback in configuration of column family "'.$props[self::CP_NAME].'"');
					}
				}
				else
				{
					$uncachedColumnFamilies[] = $props[self::CP_NAME];
				}
			}
		}
		else
		{
			$uncachedColumnFamilies = array_keys($columnFamilies);
		}
		//Get from DB
		if (count($uncachedColumnFamilies) > 0)
		{
			$dbData = self::getData($rowId, $uncachedColumnFamilies);
			if (!is_null($dbData))
			{
				$toCache = array();
				foreach ($dbData as $name => $data)
				{
					$cacheKey = static::getCacheKey($name, $rowId);
					$props = $allProperties[$cacheKey];
					if (is_callable($props[self::CP_GET_DB]))
					{
						$dataToSet = call_user_func($props[self::CP_GET_DB], $rowId, $data, $props[self::CP_PARAMS]);
						if (isset($dataToSet['cache']) && isset($dataToSet['return']))
						{
							$toCache[$cacheKey] = $dataToSet['cache'];
							$result[$name] = $dataToSet['return'];
						}
					}
					else
					{
						throw new Exception('Invalid "'.self::CP_GET_DB.'" callback in configuration of column family "'.$props[self::CP_NAME].'"');
					}
				}
				if(count($toCache) > 0)
				{
					App_Memcached::setMulti($toCache);
				}
			}
		}
		return $result;
	}
	/**
	 * Позволяет добавлять, изменять и удалять данные из указанных column families и соответсвующих им ключей Memcached
	 * @static
	 * @throws Exception
	 * @param  $rowId - идентификатор строки
	 * @param  $columnFamilies - array(<columnFamily> => 
	 * @return array
	 */
	public static function setCachedData($rowId, $columnFamilies)
	{
		//$result = array();
		$saveToDB = array();

		//Get column family properties
		$mainCacheKeys = array();
		$allProperties = array();
		self::parseColumnFamilies($rowId, $columnFamilies, $mainCacheKeys, $allProperties, false);
		//Handle all column families
		$remainingCacheKeys = $mainCacheKeys;
		$amount = count($remainingCacheKeys);
		$retryCount = 0;
		while ($amount > 0)
		{
			$uncachedColumnFamilies = array();
			//Try to get remaining column families from cache
			$index = 0;

			while ($index < $amount)
			{
				$cacheKey = $remainingCacheKeys[$index];
				$cacheToken = null;
				$cacheValue = null;
				$props = $allProperties[$cacheKey];
				//Try to get from cache
				if (App_Memcached::getWithToken($cacheKey, $cacheValue, $cacheToken))
				{
					if (is_callable($props[self::CP_SET_CACHE]))
					{
						$dataToSet = call_user_func($props[self::CP_SET_CACHE], $rowId, $cacheValue, $props[self::CP_PARAMS]);
						if (isset($dataToSet['cache']) && isset($dataToSet['db']))
						{
							if (App_Memcached::setWithToken($cacheKey, $dataToSet['cache'], $cacheToken))
							{
								//$result[$props[self::CP_NAME]] = $dataToSet['cache'];
								$saveToDB[$props[self::CP_NAME]] = $dataToSet['db'];
								$index++;
							}
							else
							{
								usleep(rand(1, 1000));
							}
						}
						else
						{
							throw new Exception('"'.self::CP_SET_CACHE.'" returned invalid result: |'.var_export($dataToSet, true).'|');
						}
					}
					else
					{
						throw new Exception('Invalid "'.self::CP_SET_CACHE.'" callback in configuration of column family "'.$props[self::CP_NAME].'"');
					}
				}
				else
				{
					$uncachedColumnFamilies[] = $props[self::CP_NAME];
					$index++;
				}
			}
			$remainingCacheKeys = array();
			if (count($uncachedColumnFamilies) > 0)
			{
				//Try to get remaining column families from DB
				$dbData = self::getData($rowId, $uncachedColumnFamilies);
				foreach($uncachedColumnFamilies as $columnFamilyName)
				{
					$cacheKey = self::getCacheKey($columnFamilyName, $rowId);
					$props = $allProperties[$cacheKey];
					if (is_callable($props[self::CP_SET_DB]))
					{
						$columnFamilyData = array();
						if (isset($dbData[$columnFamilyName]))
						{
							$columnFamilyData = $dbData[$columnFamilyName];
						}
						$dataToSet = call_user_func($props[self::CP_SET_DB], $rowId, $columnFamilyData, $props[self::CP_PARAMS]);
						if (isset($dataToSet['cache']) && isset($dataToSet['db']))
						{
							if (App_Memcached::add($cacheKey, $dataToSet['cache']))
							{
								//$result[$columnFamilyName] = $dataToSet['cache'];
								$saveToDB[$columnFamilyName] = $dataToSet['db'];
							}
							elseif ($retryCount > 2)
							{
								//Memcached::add glitch workaround: prevent infinite cycling
								//Scribe_Logger::logERR("Memcached::add glitch: failed to add value that can not be got. Internal message: '%s'.", array(App_Memcached::getResultMessage()));
								throw new Exception('Memcached::add glitch failed to add value that can not be got');
								App_Memcached::set($cacheKey, $dataToSet['cache']);
								//$result[$columnFamilyName] = $dataToSet['cache'];
								$saveToDB[$columnFamilyName] = $dataToSet['db'];
							}
							else
							{
								$retryCount++;
								$remainingCacheKeys[] = $cacheKey;
							}
						}
						else
						{
							throw new Exception('"'.self::CP_SET_DB.'" returned invalid result: |'.var_export($dataToSet, true).'|');
						}
					}
					else
					{
						throw new Exception('Invalid "'.self::CP_SET_DB.'" callback in configuration of column family "'.$props[self::CP_NAME].'"');
					}
				}
			}
			$amount = count($remainingCacheKeys);
		}
		if (count($saveToDB) > 0)
		{
			self::setData($rowId, $saveToDB);
		}
		//return $result;
	}
	protected static function parseColumnFamilies($rowId, $columnFamilies, &$getFromCache, &$allProperties, $allowEmpty = true)
	{
		if (!is_array($columnFamilies))
		{
			throw new Exception('$columnFamilies should be an array');
		}
		if (count($columnFamilies) <= 0)
		{
			if ($allowEmpty)
			{
				$columnFamilies = array_keys(static::$_families);
			}
			else
			{
				throw new Exception('At least one column family should be specified');
			}
		}
		foreach($columnFamilies as $key => $value)
		{
			$name = '';
			$params = array();
			//Parse input data
			if (is_string($key) && is_array($value))
			{
				//Column family name with params
				$name = $key;
				$params = $value;
			}
			elseif ($allowEmpty && is_string($value))
			{
				//Column family name
				$name = $value;
			}
			else
			{
				throw new Exception('Invalid key "'.serialize($key).'" with value "'.serialize($value).'" in $columnFamiles.');
			}
			//Get column family properties
			$properties = self::getColumnFamilyProperties($name, $params);
			if ($properties[self::CP_TYPE] != self::CPT_SPECIAL)
			{
				$cacheKey = static::getCacheKey($name, $rowId);
				$getFromCache[] = $cacheKey;
				$allProperties[$cacheKey] = $properties;
			}
		}
	}
	protected static function getColumnFamilyProperties($name, $params)
	{
		//Прорядок следования элементов массиве важен!!!
		$defaultProperties = array(
			self::CP_NAME => $name,
			self::CP_PARAMS => $params,
			self::CP_TYPE => self::CPT_SPECIAL,
			self::CP_GET_CACHE => null,
			self::CP_GET_DB => null,
			self::CP_SET_CACHE => null,
			self::CP_SET_DB => null
		);
		$properties = array();

		if (isset(static::$_families[$name]))
		{
			$customProperties = static::$_families[$name];
			foreach ($defaultProperties as $propertyName => $defaultValue)
			{
				if (isset($customProperties[$propertyName]))
				{
					$properties[$propertyName] = $customProperties[$propertyName];
				}
				else
				{
					$properties[$propertyName] = $defaultProperties[$propertyName];
				}
				if ($propertyName == self::CP_TYPE)
				{
					switch ($properties[$propertyName])
					{
						case self::CPT_ARRAY:
							$defaultProperties[self::CP_GET_CACHE] = function($rowId, $data, $params)
							{
								$return = array();
								$from = null;
								$size = null;
								if (isset ($params['from']))
								{
									$from = $params['from'];
								}
								if (isset($params['size']))
								{
									$size = $params['size'];
								}
								$copy = false;
								$copiedAmount = 0;
								foreach($data as $id => $val)
								{
									if ((!$copy) && (is_null($from)  || ($id == $from)))
									{
										$copy = true;
									}
									if ($copy && (is_null($size) || ($copiedAmount < $size)))
									{
										$return[$id] = $val;
										$copiedAmount++;
									}
								}
								$result = array(
									'return' => $return,
								);
								return $result;
							};
							$defaultProperties[self::CP_GET_DB] = function($rowId, $data, $params)
							{
								$return = array();
								$cache = array();
								$from = null;
								$size = null;
								if (isset ($params['from']))
								{
									$from = $params['from'];
								}
								if (isset($params['size']))
								{
									$size = $params['size'];
								}
								$copy = false;
								$copiedAmount = 0;
								foreach($data as $id => $val)
								{
									if ((!$copy) && (is_null($from)  || ($id == $from)))
									{
										$copy = true;
									}
									if ($copy && (is_null($size) || ($copiedAmount < $size)))
									{
										$return[$id] = $val['value'];
										$copiedAmount++;
									}
									$cache[$id] = $val['value'];
								}
								$result = array(
									'return' => $return,
									'cache' => $cache
								);
								return $result;
							};
							$defaultProperties[self::CP_SET_CACHE] = function($rowId, $data, $params)
							{
								$cache = array();
								$db = array();
								//Copy original data to cache
								foreach($data as $id => $value)
								{
									if((!array_key_exists($id, $params)) || (!is_null($params[$id])))
									{
										$cache[$id] = $value;
									}
								}
								//Apply changes
								foreach($params as $id => $value)
								{
									//DB changes
									if ((!isset($data[$id])) || ($data[$id] != $value))
									{
										$db[$id] = $value;
									}
									//Cache changes
									if (!is_null($value))
									{
										$cache[$id] = $value;
									}
								}
								$result = array(
									'cache' => $cache,
									'db' => $db
								);
								return $result;
							};
							$defaultProperties[self::CP_SET_DB] = function($rowId, $data, $params)
							{
								$cache = array();
								$db = array();
								//Copy original data to cache
								foreach($data as $id => $value)
								{
									if((!array_key_exists($id, $params)) || (!is_null($params[$id])))
									{
										$cache[$id] = $value['value'];
									}
								}
								//Apply changes
								foreach($params as $id => $value)
								{
									//DB changes
									if ((!isset($data[$id])) || ($data[$id] != $value))
									{
										$db[$id] = $value;
									}
									//Cache changes
									if (!is_null($value))
									{
										$cache[$id] = $value;
									}
								}
								$result = array(
									'cache' => $cache,
									'db' => $db
								);
								return $result;
							};
							break;
						case self::CPT_OBJECT:
							$defaultProperties[self::CP_GET_CACHE] = function($rowId, $data, $params)
							{
								$return = array();
								if (is_array($params) && (count($params) > 0))
								{
									foreach ($params as $columnId)
									{
										if (isset($data[$columnId]))
										{
											$return[$columnId] = $data[$columnId];
										}
									}
								}
								else
								{
									$return = $data;
								}
								$result = array(
									'return' => $return,
								);
								return $result;
							};
							$defaultProperties[self::CP_GET_DB] = function($rowId, $data, $params)
							{
								$return = array();
								$cache = array();

								foreach ($data as $id => $val)
								{
									$cache[$id] = $val['value'];
								}

								if (is_array($params) && (count($params) > 0))
								{
									foreach ($params as $columnId)
									{
										if (isset($cache[$columnId]))
										{
											$return[$columnId] = $cache[$columnId];
										}
									}
								}
								else
								{
									$return = $cache;
								}
								$result = array(
									'return' => $return,
									'cache' => $cache
								);
								return $result;
							};
							$defaultProperties[self::CP_SET_CACHE] = function($rowId, $data, $params)
							{
								$cache = array();
								$db = array();
								foreach($data as $id => $value)
								{
									if (isset($params[$id]))
									{
										$db[$id] = $params[$id];
										if (!is_null($params[$id]))
										{
											$cache[$id] = $params[$id];
										}
									}
									else
									{
										$db[$id] = $data[$id];
										$cache[$id] = $data[$id];
									}
								}
								foreach($params as $id => $value)
								{
									if ((!array_key_exists($id, $db)) && (!is_null($value)))
									{
										$db[$id] = $params[$id];
										$cache[$id] = $params[$id];
									}
								}
								$result = array(
									'cache' => $cache,
									'db' => $db
								);
								return $result;
							};
							$defaultProperties[self::CP_SET_DB] = function($rowId, $data, $params)
							{
								$cache = array();
								$db = array();
								foreach($data as $id => $value)
								{
									if (isset($params[$id]))
									{
										$db[$id] = $params[$id];
										if (!is_null($params[$id]))
										{
											$cache[$id] = $params[$id];
										}
									}
									else
									{
										$db[$id] = $data[$id]['value'];
										$cache[$id] = $data[$id]['value'];
									}
								}
								foreach($params as $id => $value)
								{
									if ((!array_key_exists($id, $db)) && (!is_null($value)))
									{
										$db[$id] = $params[$id];
										$cache[$id] = $params[$id];
									}
								}
								$result = array(
									'cache' => $cache,
									'db' => $db
								);
								return $result;
							};
							break;
						case self::CPT_SPECIAL:
							break;
						default :
							throw new Exception('Invalid cache type "'.$properties[$propertyName].'" in column family "'.$name.'"');
					}
				}

			}
		}
		else
		{
			throw new Exception('Unknown column family "' . $name . '".');
		}
		return $properties;
	}

	/**
	 * @static
	 * @param  $rowId
	 * @param  $columnFamilies[] = 'profile:'
	 * @return array
	 */
	public static function getColumnFamilies($rowId, $columnFamilies = null)
	{
		if (!isset($columnFamilies))
		{
			$columnFamilies = array_keys(static::$_families);
		}
		else
		{
			$columnFamilies = array_intersect($columnFamilies, array_keys(static::$_families));
		}
		$result = array();
		$unCachedColumnFamilies = array();
		$getFromCache = array();
		//создаем массив ключей для запроса getMulti из кэша
		foreach ($columnFamilies as $key=>$columnFamily)
		{
			$getFromCache[$key] = static::getCacheKey($columnFamily, $rowId);
			$result[$columnFamily] = array();
		}
		// Вытаскиваем масив данных и сравниваем с запрашиваемым, чего нет в кэше запросим из базы позже
		if (App_Memcached::getMulti($getFromCache, $cacheValues))
		{
			// пройдем по всем значениям запрошенным из кэша и сравним с тем что получили
			foreach($getFromCache as $key=>$value){
				// если есть, запишем в результат
				if(isset($cacheValues[$value]))
				{
					$result[$columnFamilies[$key]] = $cacheValues[$value];
				}
				// если нет, запишем в массив запроса из БД
				else
				{
					$unCachedColumnFamilies[] = $columnFamilies[$key];
				}
			}
		}
		else
		{
			$unCachedColumnFamilies = $columnFamilies;
		}

		if (count($unCachedColumnFamilies) > 0)
		{
			//Запрашиваем недостающее из БД
			$rows = self::getRowWithColumns($rowId, $unCachedColumnFamilies);
			if (count($rows) > 0)
			{
				$columns = $rows[0]->columns;
				foreach ($columns as $columnId => $value)
				{
					$columnIdParts = explode(':', $columnId);
					if (count($columnIdParts) == 2)
					{
						$result[$columnIdParts[0] . ':'][$columnIdParts[1]] = $value->value;
					}
				}
				$toCache = array();
				foreach ($unCachedColumnFamilies as $columnFamily)
				{
					$toCache[static::getCacheKey($columnFamily, $rowId)] = $result[$columnFamily];
				}
				if(count($toCache) > 0)
					App_Memcached::setMulti($toCache);
			}
			else
			{
				//$result = false;
			}
		}
		return $result;
	}

	/**
	 * @static
	 * @param array $data[rowId][]=columnFamily
	 * @return array
	 */
	public static function getColumnFamiliesForRows($data = array())
	{
		$result = array();
		$fromCache = array();
		$toGetFromCache = array();
		$toGetFromDb = array();
		$unCached = array();
		$toCache = array();

		// формируем массив запроса из memcache, и массив индексов соотносящихся каждому ключу из memcache
		foreach ($data as $rowId => $columnFamilies)
		{
			// если нет запроса определенных columnFamily, то проходим по всем возможным из базы
			if (count($columnFamilies) == 0)
			{
				$columnFamilies = array_keys(static::$_families);
			}
			$result[$rowId] = array();
			foreach ($columnFamilies as $columnFamily)
			{
				if (isset(static::$_families[$columnFamily]))
				{
					$toGetFromCache[] = static::getCacheKey($columnFamily, $rowId);
					$toGetFromDb[] = array('rowId' => $rowId, 'columnFamily' => $columnFamily);
					$result[$rowId][$columnFamily] = array();
				}
			}
		}
		unset($rowId);
		unset($columnFamily);
		App_Memcached::getMulti($toGetFromCache, $fromCache);
		// смотрим пересечение полученных из кэша данных и запрашиваемых
		/** смотри запрошенные и если есть ключ в полученных то пишем в result
		 * иначе пишем в unCache
		 */
		foreach ($toGetFromCache as $key => $value)
		{
			$rowId = $toGetFromDb[$key]['rowId'];
			$columnFamily = $toGetFromDb[$key]['columnFamily'];
			if (isset($fromCache[$value]))
			{
				$result[$rowId][$columnFamily] = $fromCache[$value];
			}
			else
			{
				if (!isset($unCached[$rowId]))
				{
					$unCached[$rowId] = array();
				}
				$unCached[$rowId][] = $columnFamily;
			}
		}
		unset($rowId);
		unset($columnFamily);
		foreach ($unCached as $rowId => $columnFamilies)
		{
			if (count($columnFamilies) > 0)
			{
				//Запрашиваем недостающее из БД
				$rows = static::getRowWithColumns($rowId, $columnFamilies);
				if (count($rows) > 0)
				{
					$columns = $rows[0]->columns;
					foreach ($columns as $columnId => $value)
					{
						$columnIdParts = explode(':', $columnId);
						if (count($columnIdParts) == 2)
						{
							$result[$rowId][$columnIdParts[0] . ':'][$columnIdParts[1]] = $value->value;
						}
					}
					foreach ($columnFamilies as $columnFamily)
					{
						$toCache[static::getCacheKey($columnFamily, $rowId)] = $result[$rowId][$columnFamily];
					}
				}
			}
		}
		if (count($toCache) > 0)
		{
			App_Memcached::setMulti($toCache);
		}

		return $result;
	}

	/**
	 * @static
	 * @param array $ids[] = $rowId
	 * @param array $columnFamilies[] = $columnFamily
	 * @return array
	 */
	public static function getColumnFamiliesForRowsAlt($ids = array(), $columnFamilies = array())
	{
		$data = array();
		// если нет запроса определенных columnFamily, то проходим по всем возможным из базы
		if (count($columnFamilies) == 0)
		{
			$columnFamilies = array_keys(static::$_families);
		}
		foreach ($ids as $rowId)
		{
			foreach ($columnFamilies as $columnFamily)
			{
				if (isset(static::$_families[$columnFamily]))
				{
					$data[$rowId][] = $columnFamily;
				}
			}
		}
		return static::getColumnFamiliesForRows($data);
	}


	/**
	 * @static
	 * @param  $rowId
	 * @param array $data[columnFamily][columnName] = $value
	 * @param array $toDelete[columnFamily][] = columnName
	 * @return void
	 */

	public static function setColumnFamilies($rowId, $data = array(), $toDelete = array())
	{
		$columnFamilies = array_keys(static::$_families);
		$mutations = array();
		foreach ($columnFamilies as $columnFamily)
		{
			$deleteCache = false;
			//Изменяем или создаем колонки
			if (isset($data[$columnFamily]))
			{
				$deleteCache = true;
				foreach ($data[$columnFamily] as $name => $value)
				{
					$mutations[] = new Mutation(array(
						'column' => $columnFamily . $name,
						'value' => (is_array($value)) ? Zend_Json::encode($value) : $value
					));
				}
			}
			//Удаляем колонки
			if (isset($toDelete[$columnFamily]))
			{
				$deleteCache = true;
				foreach ($toDelete[$columnFamily] as $name)
				{
					$mutations[] = new Mutation(array(
						'column' => $columnFamily . $name,
						'isDelete' => true
					));
				}
			}
			//Очищаем кеш
			if ($deleteCache)
			{
				App_Memcached::delete(static::getCacheKey($columnFamily, $rowId));
			}
		}
		if (count($mutations) > 0)
		{
			static::mutateRow($rowId, $mutations);
		}
	}
	/**
	 * @static
	 * @param array $data[rowId][columnFamily][columnName] = value
	 * @param array $toDelete[rowId][columnFamily][columnName] = 1
	 * @param bool $is_exist
	 * @return void
	 */
	public static function setColumnFamiliesForRows($data = array(), $toDelete = array(), $is_exist = false)
	{
		//$mutations = array();
		$cacheToClear = array();
		$batch = array();
		// формируем обновление и запоминаем что надо удалить из кэша
		foreach ($data as $rowId => $columnFamilies)
		{
			$cacheToClear[$rowId] = array();
			foreach ($columnFamilies as $columnFamily => $columns)
			{
				$mutations = array();
				if (isset(static::$_families[$columnFamily]) && (count ($columns) > 0))
				{
					foreach ($columns as $name => $value)
					{
						if(is_null($value))
						{
							$mutations[] = new Mutation(array(
								'column' => $columnFamily . $name,
								'isDelete' => true,
							));
						}
						else
						{
							$mutations[] = new Mutation(array(
								'column' => $columnFamily . $name,
								'value' => (is_array($value)) ? Zend_Json::encode($value) : $value,
							));
						}

					}
					$cacheToClear[$rowId][$columnFamily]=1;
					$batch[] = new BatchMutation(array('row' => $rowId, 'mutations' => $mutations));
				}

			}
		}
		// удаляем колонки и запоминаем что надо удалить из кэша
		foreach ($toDelete as $rowId => $columnFamilies)
		{
			foreach ($columnFamilies as $columnFamily => $columns)
			{
				$mutations = array();
				
				if (isset(static::$_families[$columnFamily]) && (count ($columns) > 0))
				{
					foreach ($columns as $name => $value)
					{
						if(!empty($name))
						{
							$mutations[] = new Mutation(array(
								'column' => $columnFamily . $name,
								'isDelete' => true,
							));
						}
					}
					$cacheToClear[$rowId][$columnFamily]=1;
					$batch[] = new BatchMutation(array('row' => $rowId, 'mutations' => $mutations));
				}

			}
		}
		//Применяем изменения
		if (count($batch) > 0)
		{
			self::mutateRows($batch);
		}
		//Чистим кеш
		if(count($cacheToClear)>0){
			foreach($cacheToClear as $key=>$value)
				foreach($value as $cfId=>$cf)
				{
					App_Memcached::delete(self::getCacheKey($cfId, $key));
				}
		}
	}

	public static function updateBatch($data = array())
	{
		$families = static::$_families;
		$batch = array();
		foreach ($data as $rowId => $columnFamilies)
		{
			foreach ($columnFamilies as $columnFamily => $columns)
			{
				if (isset($families[$columnFamily]))
				{
					$mutations = array();
					foreach ($columns as $name => $value)
					{
						if(is_null($value))
						{
							$mutations[] = new Mutation(array(
								'column' => $columnFamily . $name,
								'isDelete' => true,
							));
						}
						else
						{
							$mutations[] = new Mutation(array(
								'column' => $columnFamily . $name,
								'value' => is_array($value)?Zend_Json::encode($value):$value,
							));
						}
					}
					$batch[] = new BatchMutation(array('row' => $rowId, 'mutations' => $mutations));
				}

			}
		}
		if (count($batch) > 0)
		{
			self::mutateRows($batch);
		}
	}

	protected static function delete($table)
	{
		if (self::$_connection->isTableEnabled($table))
		{
			self::$_connection->disableTable($table);
		}
		self::$_connection->deleteTable($table);
	}

	protected static function create($table, $columns)
	{
		$columnDecriptors = array();
		foreach ($columns as $name => $props)
		{
			$columnDecriptors[] = new ColumnDescriptor(array_merge(array('name' => $name), $props));
		}
		self::$_connection->createTable($table, $columnDecriptors);
	}


	public static function get($row, $column)
	{
		//Scribe_Logger::logDBG("Row %s, Column %s", array($row, $column));
		return self::$_connection->get(static::$_table, $row, $column);
	}
	public static function getTableNames()
	{
		return self::$_connection->getTableNames();
	}
	public static function getColumnDescriptors($tableName)
	{
		return self::$_connection->getColumnDescriptors($tableName);
	}

	//public static function $1($2)\{return self::!!!_connection->$1(static::!!!_table, $2);\}

	public static function getVer($row, $column, $numVersions)
	{
		return self::$_connection->getVer(static::$_table, $row, $column, $numVersions);
	}

	public static function getVerTs($row, $column, $timestamp, $numVersions)
	{
		return self::$_connection->getVerTs(static::$_table, $row, $column, $timestamp, $numVersions);
	}

	public static function getRow($row)
	{
		return self::$_connection->getRow(static::$_table, $row);
	}

	public static function getRowWithColumns($row, $columns)
	{
		//Scribe_Logger::logDBG("Row %s, Column %s", array($row, json_encode($columns)));
		return self::$_connection->getRowWithColumns(static::$_table, $row, $columns);
	}

	public static function getRows($rows)
	{
		return self::$_connection->getRows(static::$_table, $rows);
	}
	public static function getRowsWithColumns($rows, $columns)
	{
		return self::$_connection->getRowsWithColumns(static::$_table, $rows, $columns);
	}
	public static function getRowTs($row, $timestamp)
	{
		return self::$_connection->getRowTs(static::$_table, $row, $timestamp);
	}

	public static function getRowWithColumnsTs($row, $columns, $timestamp)
	{
		return self::$_connection->getRowWithColumnsTs(static::$_table, $row, $columns, $timestamp);
	}
	public static function getRowsTs($rows, $timestamp)
	{
		return self::$_connection->getRowsTs(static::$_table, $rows, $timestamp);
	}

	public static function getRowsWithColumnsTs($rows, $columns, $timestamp)
	{
		return self::$_connection->getRowsWithColumnsTs(static::$_table, $rows, $columns, $timestamp);
	}

	public static function mutateRow($row, $mutations)
	{
		return self::$_connection->mutateRow(static::$_table, $row, $mutations);
	}

	public static function mutateRowTs($row, $mutations, $timestamp)
	{
		return self::$_connection->mutateRowTs(static::$_table, $row, $mutations, $timestamp);
	}

	public static function mutateRows($rowBatches)
	{
		return self::$_connection->mutateRows(static::$_table, $rowBatches);
	}

	public static function mutateRowsTs($rowBatches, $timestamp)
	{
		return self::$_connection->mutateRowsTs(static::$_table, $rowBatches, $timestamp);
	}

	public static function atomicIncrement($row, $column, $value)
	{
		return self::$_connection->atomicIncrement(static::$_table, $row, $column, $value);
	}

	public static function deleteAll($row, $column)
	{
		return self::$_connection->deleteAll(static::$_table, $row, $column);
	}

	public static function deleteAllTs($row, $column, $timestamp)
	{
		return self::$_connection->deleteAllTs(static::$_table, $row, $column, $timestamp);
	}

	public static function deleteAllRow($row)
	{
		return self::$_connection->deleteAllRow(static::$_table, $row);
	}

	public static function deleteAllRowTs($row, $timestamp)
	{
		return self::$_connection->deleteAllRowTs(static::$_table, $row, $timestamp);
	}

	public static function scannerOpen($startRow, $columns)
	{
		return self::$_connection->scannerOpen(static::$_table, $startRow, $columns);
	}

	public static function scannerOpenWithStop($startRow, $stopRow, $columns)
	{
		return self::$_connection->scannerOpenWithStop(static::$_table, $startRow, $stopRow, $columns);
	}

	public static function scannerOpenWithPrefix($startAndPrefix, $columns)
	{
		return self::$_connection->scannerOpenWithPrefix(static::$_table, $startAndPrefix, $columns);
	}

	public static function scannerOpenTs($startRow, $columns, $timestamp)
	{
		return self::$_connection->scannerOpenTs(static::$_table, $startRow, $columns, $timestamp);
	}

	public static function scannerOpenWithStopTs($startRow, $stopRow, $columns, $timestamp)
	{
		return self::$_connection->scannerOpenWithStopTs(static::$_table, $startRow, $stopRow, $columns, $timestamp);
	}

	public static function scannerGet($id)
	{
		return self::$_connection->scannerGet($id);
	}

	public static function scannerGetList($id, $nbRows)
	{
		return self::$_connection->scannerGetList($id, $nbRows);
	}

	public static function scannerClose($id)
	{
		return self::$_connection->scannerClose($id);
	}

	public static function setTable($table)
	{
		self::$_table = $table;
	}

	public static function getTable()
	{
		return self::$_table;
	}
	public static function convertToHBaseTimestamp($timestamp)
	{
		return ($timestamp * 1000);
	}

}
