<?php

namespace platx\settings;

use yii\base\Model;
use yii\helpers\Json;


/**
 * Form for update settings in section
 * @package platx\settings
 */
class SettingForm extends Model
{
    /**
     * @var array Model attribute collection
     */
    private $_settingModels = [];

    /**
     * @return array Attribute labels
     */
    public function attributeLabels()
    {
        $items = [];
        foreach ($this->_settingModels as $key => $setting) {
            $items[$key] = $setting->name;
        }

        return $items;
    }

    /**
     * @return array Validation rules
     */
    public function rules()
    {
        $items = [];
        foreach ($this->_settingModels as $key => $setting) {
            if (!empty($setting->rules)) {
                $rules = Json::decode($setting->rules, true);
                foreach ($rules as $rule) {
                    $items[] = array_merge([$key], $rule);
                }
            } else {
                $items[] = [$key, 'safe'];
            }
        }

        return $items;
    }

    /**
     * Gets model attributes
     * @param null $names
     * @param array $except
     * @return array
     */
    public function getAttributes($names = null, $except = [])
    {
        $items = [];
        foreach ($this->_settingModels as $key => $model) {
            $items[$key] = $model->value;
        }

        return $items;
    }

    /**
     * Gets setting models collection
     * @return array
     */
    public function getSettings()
    {
        return $this->_settingModels;
    }

    /**
     * Saves settings
     * @return array|bool
     */
    public function save()
    {
        /**
         * @var string $key
         * @var Setting $setting
         */
        foreach ($this->_settingModels as $key => $setting) {
            if (!$setting->validate()) {
                $this->addErrors([$key => $setting->errors]);
            }
        }

        if (!$this->hasErrors()) {
            foreach ($this->_settingModels as $key => $setting) {
                Setting::set("{$setting->section}.{$key}", $setting->value);
            }

            return true;
        } else {
            return false;
        }
    }

    /**
     * Loads settings for section
     * @param string $section
     * @return SettingForm
     */
    public function loadBySection($section = 'general')
    {
        $models = Setting::find()
            ->where(['section' => $section])
            ->indexBy('key')
            ->orderBy('position ASC')
            ->all();

        if (!empty($models)) {
            $this->_settingModels = $models;
            return true;
        }

        return false;
    }

    /**
     * Sets new value for setting
     * @param string $key
     * @param mixed $value
     */
    public function __set($key, $value)
    {
        if (!empty($this->_settingModels[$key])) {
            $this->_settingModels[$key]->value = $value;
        }
    }

    /**
     * Gets setting value
     * @param string $key
     * @return mixed|null
     */
    public function __get($key)
    {
        if (!empty($this->_settingModels[$key])) {
            return $this->_settingModels[$key]->value;
        }

        return null;
    }
}
