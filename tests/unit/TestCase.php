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
    private $dbConnection;

    protected function setUp()
    {
        parent::setUp();
        $databases = self::getParam('databases');
        $this->database = $databases[$this->driverName];
        $pdo_database = 'pdo_'.$this->driverName;

        if (!extension_loaded('pdo') || !extension_loaded($pdo_database)) {
            $this->markTestSkipped('pdo and '.$pdo_database.' extension are required.');
        }
        $this->mockApplication();
    }

    protected function tearDown()
    {
        parent::tearDown();
        $this->destroyApplication();
        if ($this->dbConnection) {
            $this->dbConnection->close();
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
     * Populates Yii::$app with a new application
     * The application will be destroyed on tearDown() automatically.
     * @param array $config The application configuration, if needed
     * @param string $appClass name of the application class to create
     */
    protected function mockApplication($config = [], $appClass = '\yii\console\Application')
    {
        new $appClass(ArrayHelper::merge([
            'id' => 'testapp',
            'basePath' => __DIR__,
            'vendorPath' => dirname(dirname(__DIR__)),
            'components' => [
                'db' => $this->getConnection(),
            ],
        ], $config));
    }

    /**
     * Destroys application in Yii::$app by setting it to null.
     */
    protected function destroyApplication()
    {
        \Yii::$app = null;
    }

    /**
     * @param  boolean $reset whether to clean up the test database
     * @param  boolean $open  whether to open and populate test database
     * @return \yii\db\Connection
     */
    public function getConnection($reset = true, $open = true)
    {
        if (!$reset && $this->dbConnection) {
            return $this->dbConnection;
        }
        $config = $this->database;
        if (isset($config['fixture'])) {
            $fixture = $config['fixture'];
            unset($config['fixture']);
        } else {
            $fixture = null;
        }
        try {
            $this->dbConnection = $this->prepareDatabase($config, $fixture, $open);
        } catch (\Exception $e) {
            $this->markTestSkipped("Something wrong when preparing database: " . $e->getMessage());
        }
        return $this->dbConnection;
    }

    public function prepareDatabase($config, $fixture, $open = true)
    {
        if (!isset($config['class'])) {
            $config['class'] = 'yii\db\Connection';
        }
        /* @var $db \yii\db\Connection */
        $dbConnection = \Yii::createObject($config);
        if (!$open) {
            return $dbConnection;
        }
        $dbConnection->open();
        if ($fixture !== null) {
            $lines = explode(';', file_get_contents($fixture));
            foreach ($lines as $line) {
                if (trim($line) !== '') {
                    $dbConnection->pdo->exec($line);
                }
            }
        }
        return $dbConnection;
    }
}
