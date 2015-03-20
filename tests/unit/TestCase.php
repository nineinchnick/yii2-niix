<?php

namespace nineinchnick\niix\tests\unit;

use yii\helpers\ArrayHelper;

/**
 * This is the base class for all yii framework unit tests.
 */
abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    public static $params;

    protected $database;
    protected $driverName = 'sqlite';
    /**
     * @var Connection
     */
    private $_db;

    protected function setUp()
    {
        parent::setUp();
        $databases = self::getParam('databases');
        $this->database = $databases[$this->driverName];
        $pdo_database = 'pdo_'.$this->driverName;

        if (!extension_loaded('pdo') || !extension_loaded($pdo_database)) {
            $this->markTestSkipped('pdo and '.$pdo_database.' extension are required.');
        }
    }

    protected function tearDown()
    {
        if ($this->_db) {
            $this->_db->close();
        }
    }

    /**
     * Returns a test configuration param from /data/config.php
     * @param  string $name params name
     * @param  mixed $default default value to use when param is not set.
     * @return mixed  the value of the configuration param
     */
    public static function getParam($name, $default = null)
    {
        if (static::$params === null) {
            static::$params = require(__DIR__ . '/data/config.php');
        }

        return isset(static::$params[$name]) ? static::$params[$name] : $default;
    }

    /**
     * @param  boolean $reset whether to clean up the test database
     * @param  boolean $open  whether to open and populate test database
     * @return \yii\db\Connection
     */
    public function getConnection($reset = true, $open = true)
    {
        if (!$reset && $this->_db) {
            return $this->_db;
        }
        $config = $this->database;
        if (isset($config['fixture'])) {
            $fixture = $config['fixture'];
            unset($config['fixture']);
        } else {
            $fixture = null;
        }
        try {
            $this->_db = $this->prepareDatabase($config, $fixture, $open);
        } catch (\Exception $e) {
            $this->markTestSkipped("Something wrong when preparing database: " . $e->getMessage());
        }
        return $this->_db;
    }

    public function prepareDatabase($config, $fixture, $open = true)
    {
        if (!isset($config['class'])) {
            $config['class'] = 'yii\db\Connection';
        }
        /* @var $db \yii\db\Connection */
        $db = \Yii::createObject($config);
        if (!$open) {
            return $db;
        }
        $db->open();
        if ($fixture !== null) {
            $lines = explode(';', file_get_contents($fixture));
            foreach ($lines as $line) {
                if (trim($line) !== '') {
                    $db->pdo->exec($line);
                }
            }
        }
        return $db;
    }
}
