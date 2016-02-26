<?php
namespace prime\models\ar;


use Befound\ActiveRecord\ActiveRecord;
use Befound\ActiveRecord\Behaviors\JsonBehavior;

class Setting extends ActiveRecord
{
    protected static $config = [];

    public static function get($key, $default = null) {
        // Load configuration.
        if (!array_key_exists($key, self::$config)) {
            self::$config[$key] =
                (null !== $model = self::findOne(['key' => $key]))
                    ? json_decode($model->value, true)
                    : (!isset($default) && isset(app()->params['defaultSettings'][$key])
                        ? app()->params['defaultSettings'][$key]
                        : $default);
        }

        return self::$config[$key];
    }

    public static function set($key, $value) {
        // Get existing.
        $model = self::findOne(['key' => $key]);

        if (!isset($model)) {
            $model = new self();
        }

        $model->key = $key;
        $model->value = json_encode($value);
        self::$config[$key] = $value;
        return $model->save();
    }

    public function getDecodedValue() {
        return json_decode($this->value);
    }
}