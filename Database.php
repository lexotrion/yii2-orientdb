<?php

namespace phantomd\orientdb;

use yii\base\Object;
use Yii;
use yii\helpers\Json;

/**
 * Database represents the Orient database information.
 *
 * @property string $name Name of this database. This property is read-only.
 *
 * @author Anton Ermolovich <anton.ermolovich@gmail.com>
 * @since 2.0
 */
class Database extends Object
{

    /**
     * @var \PhpOrient\PhpOrient OrientDb database instance.
     */
    public $orientDb;

    /**
     * @var string Database name.
     */
    public $database;

    /**
     * @return string Database name
     */
    public function __toString()
    {
        return $this->database;
    }

    /**
     * @return string name of this database.
     */
    public function getName()
    {
        return $this->__toString();
    }

    /**
     * Creates new collection.
     * Note: Orient creates new collections automatically on the first demand,
     * this method makes sense only for the migration script or for the case
     * you need to create collection with the specific options.
     * @param string $name name of the collection
     * @param array $options collection options in format: "name" => "value"
     * @return \OrientCollection new Orient collection instance.
     * @throws Exception on failure.
     */
    public function createCollection($name, $options = [])
    {
        $token = $this->getName() . '.create(' . $name . ', ' . Json::encode($options) . ')';
        Yii::info($token, __METHOD__);
        try {
            Yii::beginProfile($token, __METHOD__);
            $result = $this->orientDb->createCollection($name, $options);
            Yii::endProfile($token, __METHOD__);

            return $result;
        } catch (\Exception $e) {
            Yii::endProfile($token, __METHOD__);
            throw new Exception($e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * Executes Orient command.
     * @param array $command command specification.
     * @param array $options options in format: "name" => "value"
     * @return array database response.
     * @throws Exception on failure.
     */
    public function executeCommand($command, $options = [])
    {
        $token = $this->getName() . '.$cmd(' . Json::encode($command) . ', ' . Json::encode($options) . ')';
        Yii::info($token, __METHOD__);
        try {
            Yii::beginProfile($token, __METHOD__);
            $result = $this->orientDb->command($command, $options);
            $this->tryResultError($result);
            Yii::endProfile($token, __METHOD__);

            return $result;
        } catch (\Exception $e) {
            Yii::endProfile($token, __METHOD__);
            throw new Exception($e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * Checks if command execution result ended with an error.
     * @param mixed $result raw command execution result.
     * @throws Exception if an error occurred.
     */
    protected function tryResultError($result)
    {
        if (is_array($result)) {
            if (!empty($result['errmsg'])) {
                $errorMessage = $result['errmsg'];
            } elseif (!empty($result['err'])) {
                $errorMessage = $result['err'];
            }
            if (isset($errorMessage)) {
                if (array_key_exists('ok', $result)) {
                    $errorCode = (int)$result['ok'];
                } else {
                    $errorCode = 0;
                }
                throw new Exception($errorMessage, $errorCode);
            }
        } elseif (!$result) {
            throw new Exception('Unknown error, use "w=1" option to enable error tracking');
        }
    }

}
