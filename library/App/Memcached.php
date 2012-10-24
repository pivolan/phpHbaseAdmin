<?php
/**
 * Статический класс-обертка для работы с Memcached.
 * Настриавается через application.ini.
 * Поддерживаются все опции из официальной документации с http://ru2.php.net/manual/en/memcached.constants.php
 * Пример строки конфигурации
 * memcached.serializer = json
 */

class App_Memcached
{
	//Memcached instance
	protected static $_memcached = null;

	/**
	 * Инициализатор класса. Должен быть вызван перед любым использованием класса.
	 * @static
	 * @param  $persistentId - уникальный идентификатор пула серверов Memcached, соединение до которого сохраняется между отдельными запросами
	 * @param  $serverStrs - список серверов пула в формате <ip_or_name>[:<port>[:<weight>]]
	 * @param array $options - список конфигураионных параметоров Memcached
	 * @return void
	 */
	public static function init($persistentId, array $serverStrs, array $options = array())
	{
		self::$_memcached = new Memcached($persistentId);
		//Check if persistent memcached pool has not already been set
		if (count(self::$_memcached->getServerList()) <= 0)
		{
			//Setting servers
			$servers = array();
			foreach ($serverStrs as $str)
			{
				$strParts = explode(':', $str);
				$partQty = count($strParts);
				$host = $strParts[0];
				$port = ($partQty > 0) ? $strParts[1] : 11211;
				$weight = ($partQty > 1) ? $strParts[2] : 0;
				$servers[] = array($host, $port, $weight);
			}
			self::$_memcached->addServers($servers);
			//Setting options
			foreach ($options as $key => $value)
			{
				self::setOption($key, $value);
			}
		}
	}

	/**
	 * Устанавливает значение конфигурационнного параметра для внутрнного экземпляра Memcached.
	 * @static
	 * @param  $name
	 * @param  $value
	 * @return void
	 */
	protected static function setOption($name, $value)
	{
		switch (strtoupper($name))
		{
			case 'COMPRESSION' :
				self::$_memcached->setOption(Memcached::OPT_COMPRESSION, $value);
				break;

			case 'SERIALIZER' :
				switch (strtoupper($value))
				{
					case 'PHP' :
						$value = Memcached::SERIALIZER_PHP;
						break;
					case 'IGBINARY' :
						$value = Memcached::SERIALIZER_IGBINARY;
						break;
					case 'JSON' :
						$value = Memcached::SERIALIZER_JSON;
						break;
				}
				self::$_memcached->setOption(Memcached::OPT_SERIALIZER, $value);
				break;

			case 'PREFIX_KEY' :
				self::$_memcached->setOption(Memcached::OPT_PREFIX_KEY, $value);
				break;

			case 'HASH' :
				switch (strtoupper($value))
				{
					case 'DEFAULT' :
						$value = Memcached::HASH_DEFAULT;
						break;
					case 'MD5' :
						$value = Memcached::HASH_MD5;
						break;
					case 'CRC' :
						$value = Memcached::HASH_CRC;
						break;
					case 'FNV1' :
						$value = Memcached::HASH_FNV1;
						break;
					case 'FNV1' :
						$value = Memcached::HASH_FNV1;
						break;
					case 'FNV1' :
						$value = Memcached::HASH_FNV1;
						break;
					case 'FNV1' :
						$value = Memcached::HASH_FNV1;
						break;
					case 'HSIEH' :
						$value = Memcached::HASH_HSIEH;
						break;
					case 'MURMUR' :
						$value = Memcached::HASH_MURMUR;
						break;
				}
				self::$_memcached->setOption(Memcached::OPT_HASH, $value);
				break;

			case 'DISTRIBUTION' :
				switch (strtoupper($value))
				{
					case 'MODULA' :
						$value = Memcached::DISTRIBUTION_MODULA;
						break;
					case 'CONSISTENT' :
						$value = Memcached::DISTRIBUTION_CONSISTENT;
						break;
				}
				self::$_memcached->setOption(Memcached::OPT_DISTRIBUTION, $value);
				break;

			case 'LIBKETAMA_COMPATIBLE' :
				self::$_memcached->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, $value);
				break;

			case 'BUFFER_WRITES' :
				self::$_memcached->setOption(Memcached::OPT_BUFFER_WRITES, $value);
				break;

			case 'BINARY_PROTOCOL' :
				self::$_memcached->setOption(Memcached::OPT_BINARY_PROTOCOL, $value);
				break;

			case 'NO_BLOCK' :
				self::$_memcached->setOption(Memcached::OPT_NO_BLOCK, $value);
				break;

			case 'TCP_NODELAY' :
				self::$_memcached->setOption(Memcached::OPT_TCP_NODELAY, $value);
				break;

			case 'SOCKET_SEND_SIZE' :
				self::$_memcached->setOption(Memcached::OPT_SOCKET_SEND_SIZE, $value);
				break;

			case 'SOCKET_RECV_SIZE' :
				self::$_memcached->setOption(Memcached::OPT_SOCKET_RECV_SIZE, $value);
				break;

			case 'CONNECT_TIMEOUT' :
				self::$_memcached->setOption(Memcached::OPT_CONNECT_TIMEOUT, $value);
				break;

			case 'RETRY_TIMEOUT' :
				self::$_memcached->setOption(Memcached::OPT_RETRY_TIMEOUT, $value);
				break;

			case 'SEND_TIMEOUT' :
				self::$_memcached->setOption(Memcached::OPT_SEND_TIMEOUT, $value);
				break;

			case 'RECV_TIMEOUT' :
				self::$_memcached->setOption(Memcached::OPT_RECV_TIMEOUT, $value);
				break;

			case 'POLL_TIMEOUT' :
				self::$_memcached->setOption(Memcached::OPT_POLL_TIMEOUT, $value);
				break;

			case 'CACHE_LOOKUPS' :
				self::$_memcached->setOption(Memcached::OPT_CACHE_LOOKUPS, $value);
				break;

			case 'SERVER_FAILURE_LIMIT' :
				self::$_memcached->setOption(Memcached::OPT_SERVER_FAILURE_LIMIT, $value);
				break;
		}
	}

	/**
	 * Добавляет ключ-значение в Memcached, если такого ключа там еще нет
	 * @static
	 * @param  $key
	 * @param  $value
	 * @param int $expiration
	 * @return bool - была ли операция успешна
	 */
	public static function add($key, $value, $expiration = 0)
	{
		return self::$_memcached->add($key, $value, $expiration);
	}

	/**
	 * Уменьшает значение по заданному ключу на указанную величин до тех пор пока оно не станет равным 0
	 * @static
	 * @param  $key
	 * @param int $offset
	 * @return FALSE | int
	 */
	public static function decrement($key, $offset = 1)
	{
		return self::$_memcached->decrement($key, $offset);
	}

	/**
	 * Удаляет указанный ключ из Memcached
	 * @static
	 * @param  $key
	 * @param int $time - время, в течении которого ключ будет недоступен для операций add и replace
	 * @return bool - была ли операция успешна
	 */
	public static function delete($key, $time = 0)
	{
		return self::$_memcached->delete($key, $time);
	}

	/**
	 * Удаляет все ключи из Memcached
	 * @static
	 * @param int $delay - задержка удаления
	 * @return bool - была ли операция успешна
	 */
	public static function flush($delay = 0)
	{
		return self::$_memcached->flush($delay);
	}

	/**
	 * Возвращает значение, хранящееся по указанноому ключу
	 * @static
	 * @param  $key
	 * @param  $value - переменная, в которую записывается значение
	 * @return bool - была ли операция успешна
	 */
	public static function get($key, &$value)
	{
		//$defaultVal = $value;
		$value = self::$_memcached->get($key);
		/*if (self::$_memcached->getResultCode() == Memcached::RES_NOTFOUND)
		{
			$value = $defaultVal;
		}*/
		return ($value !== false) && ($value !== null);
	}

	/**
	 * Возвращает значение, хранящееся по указанному ключу вместе с его проверочным токеном.
	 * @static
	 * @param  $key
	 * @param  $value - переменная, в которую записывается значение
	 * @param  $casToken - переменная, вкоторую записывается проверочный токен
	 * @return bool - была ли операция успешна
	 */
	public static function getWithToken($key, &$value, &$casToken)
	{
		//Scribe_Logger::logDBG("key '%s'", array($key));
		$value = self::$_memcached->get($key, null, $casToken);
		return ($value !== false) && ($value !== null);
	}

	/**
	 * Возвращает массив значений, хранящихся по указанным ключам
	 * @static
	 * @param  $keys
	 * @param  $values - переменная, в которую записывается массив с полученными значениями
	 * @return bool - была ли операция успешна
	 */
	public static function getMulti(array $keys, &$values)
	{
		$values = self::$_memcached->getMulti($keys);
		return ($values !== false);
	}

	/**
	 * Увеличивает значение, хранящееся по указанному ключу, на указанную величину
	 * @static
	 * @param  $key
	 * @param int $offset
	 * @return FALSE | int
	 */
	public static function increment($key, $offset = 1)
	{
		return self::$_memcached->increment($key, $offset);
	}

	/**
	 * Замещает значение, хранящееся по указанному ключу, новым.
	 * @static
	 * @param  $key
	 * @param  $value
	 * @param int $expiration - время или дата, когда новое значение должно устареть
	 * @return bool - была ли операция успешна
	 */
	public static function replace($key, $value, $expiration = 0)
	{
		return self::$_memcached->replace($key, $value, $expiration);
	}

	/**
	 * Устанавливает ключ-значение в Memcached
	 * @static
	 * @param  $key
	 * @param  $value
	 * @param int $expiration - время или дата, когда значение должно устареть
	 * @return bool - была ли операция успешна
	 */
	public static function set($key, $value, $expiration = 0)
	{
		return self::$_memcached->set($key, $value, $expiration);
	}

	/**
	 * Устанавливает новое значение для ключа, ранее извлеченного вместе с провероым токеном.
	 * Если проверочный токен изменился с момента извлечения (значение успел поменять кто-то другой)
	 * то новое значение не устанавливается
	 * @static
	 * @param  $key
	 * @param  $value
	 * @param  $casToken - проверочный токен
	 * @param int $expiration - время или дата, когда значение должно устареть
	 * @return bool - была ли операция успешна
	 */
	public static function setWithToken($key, $value, $casToken, $expiration = 0)
	{
		return self::$_memcached->cas($casToken, $key, $value, $expiration);
	}

	/**
	 * Устанавливает несколько пар ключ-значение в Memcached
	 * @static
	 * @param  $items - ассоциативный массив key=>value
	 * @param int $expiration - время или дата, когда значения должны устареть
	 * @return bool - была ли операция успешна
	 */
	public static function setMulti(array $items, $expiration = 0)
	{
		return self::$_memcached->setMulti($items, $expiration);
	}

	public static function getResultCode()
	{
		return self::$_memcached->getResultCode();
	}
	public static function getResultMessage()
	{
		return self::$_memcached->getResultMessage();
	}
}
