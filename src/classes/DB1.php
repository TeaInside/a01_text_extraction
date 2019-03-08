<?php

defined("PDO_1_PARAMETERS") or die("PDO_1_PARAMETERS is not defined yet!\n");

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @version 0.0.1
 */
final class DB1
{
	/**
	 * @var self
	 */
	private static $instance;

	/**
	 * @var \PDO
	 */
	private $pdo;

	/**
	 * Constructor.
	 */
	private function __construct()
	{
		$this->initPdo();
	}

	/**
	 * @return void
	 */
	private function initPdo(): void
	{
		$this->pdo = new PDO(...PDO_1_PARAMETERS);
	}

	/**
	 * @return self
	 */
	public static function &getInstance(): DB1
	{
		if (!(self::$instance instanceof DB1)) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * @return \PDO
	 */
	public static function &pdo(): PDO
	{
		$pdo = &self::getInstance()->pdo;
		if (!($pdo instanceof PDO)) {
			self::getInstance()->initPdo();
			$pdo = &self::getInstance()->pdo;
		}
		return $pdo;
	}

	/**
	 * @return void
	 */
	public static function close(): void
	{
		self::getInstance()->pdo = null;
	}

	/**
	 * Destructor.
	 */
	public function __destruct()
	{
		self::getInstance()->pdo = null;
	}
}
