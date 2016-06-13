<?php


namespace platx\settings;

use Yii;
use yii\base\Exception;
use yii\base\InvalidValueException;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\helpers\Json;
use yii\web\UploadedFile;


/**
 * Settings model
 *
 * @property string $section Setting section
 * @property string $key Setting key
 * @property string $name Name of setting
 * @property string $hint Hint for setting updating field
 * @property string $value Default value of setting
 * @property integer $type_key Input type for setting update
 * @property string $variants Position in section
 * @property string $rules List of parameters for dropdown, radio or checkbox list
 * @property string $position Validation rules for setting update
 */
class Setting extends ActiveRecord
{
    /**
     * Input type constants
     */
    const TYPE_TEXT = 0;
    const TYPE_TEXTAREA = 1;
    const TYPE_EDITOR = 2;
    const TYPE_SELECTBOX = 3;
    const TYPE_SELECTBOX_MULTIPLE = 4;
    const TYPE_CHECKBOX = 5;
    const TYPE_RADIO = 6;
    const TYPE_RADIOLIST = 7;
    const TYPE_FILE = 8;

    /**
     * Cache key for saving settings in cache
     * @var string
     */
    private static $cacheKey = 'setting';

    /**
     * @var string|null|UploadedFile
     */
    private $_value;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%setting}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = [
            [['value'], 'string'],
        ];

        if ($this->isNewRecord) {
            $rules = ArrayHelper::merge($rules, [
                [['section', 'key'], 'required'],
                [['section', 'key'], 'string', 'max' => 50],
                ['name', 'required'],
                ['name', 'string', 'max' => 100],
                ['hint', 'string', 'max' => 255],
                [['type_key', 'position'], 'integer'],
                [['variants', 'rules'], 'validatorIsArray'],
            ]);
        }

        return $rules;
    }

    public function beforeValidate()
    {
        if ($this->type_key == self::TYPE_FILE && $file = UploadedFile::getInstance(new SettingForm(), $this->key)) {
            $this->_value = $file;
        }

        return parent::beforeValidate();
    }

    /**
     * Checks attribute valuefor array
     * @param $attribute
     */
    public function validatorIsArray($attribute)
    {
        if (!is_array($this->{$attribute})) {
            $this->addError($attribute, 'Must be array');
        }
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'section' => 'Section',
            'key' => 'Key',
            'name' => 'Name',
            'hint' => 'Hint',
            'value' => 'Value',
            'type_key' => 'Input type',
            'variants' => 'Variants',
            'rules' => 'Rules',
            'position' => 'Position',
        ];
    }

    /**
     * Before save
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        $this->_autoIncrementPosition();

        if ($this->type_key == self::TYPE_FILE) {
            $this->_uploadFile();
        }

        if (is_array($this->variants)) {
            $this->variants = Json::encode($this->variants);
        }
        if (is_array($this->rules)) {
            $this->rules = Json::encode($this->rules);
        }

        return parent::beforeSave($insert);
    }

    /**
     * Auto set position
     * if it didn't set in migration
     */
    private function _autoIncrementPosition()
    {
        if (empty($this->position)) {
            $maxPositionInSection = self::find()
                ->where(['section' => $this->section])
                ->max("position");
            $this->position = !empty($maxPositionInSection) ? $maxPositionInSection + 1 : 1;
        }
    }

    /**
     * @throws Exception
     */
    protected function _uploadFile()
    {
        if (!empty($this->_value) && !($this->_value instanceof UploadedFile)) {
            throw new InvalidValueException("Setting {$this->section}.{$this->key} must be a file!");
        }
        $fileName = "{$this->section}_{$this->key}_" . uniqid() . '.' . $this->_value->extension;

        $url = "/uploads/settings/{$fileName}";
        $path = Yii::getAlias("@frontend/web{$url}");

        if (is_string($path) && FileHelper::createDirectory(dirname($path))) {
            if ($this->_value instanceof UploadedFile) {
                if ($this->_value->saveAs($path, true)) {
                    $this->value = $url;
                    unset($this->_value);
                }
            }
        }
    }

    /**
     * Get variants for list as an array
     * @return mixed
     */
    public function getVariants()
    {
        return Json::decode($this->variants, true);
    }

    /**
     * Gets setting value
     * or section settings
     * @param $key
     * @param null|mixed $defaultValue
     * @return mixed|null
     * @throws Exception
     */
    public static function get($key, $defaultValue = null)
    {
        if (strpos($key, '.') !== false) {
            $pieces = explode('.', $key, 2);
            $section = $pieces[0];
            $key = $pieces[1];
        } else {
            $section = $key;
            $key = null;
        }

        // Если указан ключ, тогда пытаемся его получить из кеша
        // в противном случае будем получать всю секцию
        $value = !is_null($key) && !empty(Yii::$app->cache) ?
            Yii::$app->cache->get(self::$cacheKey . '_' . $section . '_' . $key) : false;

        if ($value === false) {
            $query = static::find()->where(['section' => $section]);

            if (!is_null($key)) {
                $query->andWhere(['key' => $key]);

                /** @var static $model */
                $model = $query->one();

                if (empty($model)) {
                    throw new Exception("Key {$section}.{$key} not found!");
                }

                $value = $model->value;

                if (!empty(Yii::$app->cache)) {
                    Yii::$app->cache->set(self::$cacheKey . '_' . $section . '_' . $key, $value);
                }
            } else {
                $value = $query->all();
            }
        }

        return !empty($value) ? $value : $defaultValue;
    }

    /**
     * Save setting value
     * @param $key
     * @param $value
     * @return bool
     * @throws Exception
     */
    public static function set($key, $value)
    {
        if (strpos($key, '.') !== false) {
            $pieces = explode('.', $key, 2);
            $section = $pieces[0];
            $key = $pieces[1];
        } else {
            throw new Exception("Key must be in section.key format!");
        }

        /** @var static $model */
        $model = self::find()
            ->where(['key' => $key, 'section' => $section])
            ->one();

        if (empty($model)) {
            throw new Exception("Key {$section}.{$key} not found!");
        }

        if (!empty($model)) {
            $model->value = $value;

            if ($model->save()) {
                if (!empty(Yii::$app->cache)) {
                    Yii::$app->cache->delete(self::$cacheKey . '_' . $section . '_' . $key);
                }

                return true;
            }
        }

        return false;
    }
}