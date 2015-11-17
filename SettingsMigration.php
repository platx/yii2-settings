<?php

namespace platx\settings;

use yii\db\Expression;
use yii\db\Migration;
use yii\db\Query;


/**
 * Migration for insert new settings to settings list
 * @package platx\settings
 */
abstract class SettingsMigration extends Migration
{
    /**
     * Config constants
     */
    const FIELD_SECTION = 'section'; // Setting section
    const FIELD_KEY = 'key'; // Setting key
    const FIELD_NAME = 'name'; // Name of setting
    const FIELD_HINT = 'hint'; // Hint for setting updating field
    const FIELD_VALUE = 'value'; // Default value of setting
    const FIELD_TYPE = 'type_key'; // Input type for setting update
    const FIELD_POSITION = 'position'; // Position in section
    const FIELD_VARIANTS = 'variants'; // List of parameters for dropdown, radio or checkbox list
    const FIELD_RULES = 'rules'; // Validation rules for setting update

    /**
     * Input type constants for type_key field
     */
    const TYPE_TEXT = 0;
    const TYPE_TEXTAREA = 1;
    const TYPE_EDITOR = 2;
    const TYPE_SELECTBOX = 3;
    const TYPE_SELECTBOX_MULTIPLE = 4;
    const TYPE_CHECKBOX = 5;
    const TYPE_RADIO = 6;
    const TYPE_RADIOLIST = 7;

    /**
     * @var array Settings array for insert
     * Example:
     * protected $_rows = [
     *    [
     *       self::FIELD_SECTION => 'general',
     *       self::FIELD_KEY => 'string_setting',
     *       self::FIELD_NAME => 'String setting',
     *       self::FIELD_HINT => 'Text setting with max value = 255',
     *       self::FIELD_VALUE => 'Here some default value of setting',
     *       self::FIELD_TYPE => Setting::TYPE_TEXT,
     *       self::FIELD_RULES => [
     *           ['string', 'max' => 255],
     *       ]
     *    ],
     *    [
     *       self::FIELD_SECTION => 'general',
     *       self::FIELD_KEY => 'integer_setting',
     *       self::FIELD_NAME => 'Integer setting',
     *       self::FIELD_HINT => 'Setting with integer value',
     *       self::FIELD_VALUE => '235',
     *       self::FIELD_TYPE => Setting::TYPE_TEXT,
     *       self::FIELD_RULES => [
     *           ['integer', 'max' => 500],
     *       ]
     *    ],
     *    [
     *       self::FIELD_SECTION => 'general',
     *       self::FIELD_KEY => 'text_setting',
     *       self::FIELD_NAME => 'Text setting',
     *       self::FIELD_HINT => 'Setting with big text value',
     *       self::FIELD_VALUE => 'some big text',
     *       self::FIELD_TYPE => Setting::TYPE_TEXTAREA,
     *       self::FIELD_RULES => [
     *           ['string'],
     *       ]
     *    ],
     *    [
     *       self::FIELD_SECTION => 'general',
     *       self::FIELD_KEY => 'checkbox_setting',
     *       self::FIELD_NAME => 'Checkbox setting',
     *       self::FIELD_HINT => 'Checkbox setting with true or false condition',
     *       self::FIELD_VALUE => '0',
     *       self::FIELD_TYPE => Setting::TYPE_CHECKBOX,
     *       self::FIELD_RULES => [
     *           ['integer'],
     *       ]
     *    ],
     * ];
     */
    protected $_rows = null;

    /**
     * Checks $_rows parameter
     */
    public function init()
    {
        if ($this->_rows == null) {
            echo "Migration is empty\n";
            exit;
        }

        parent::init();
    }

    /**
     * Migration Up
     * @return bool
     * @throws \yii\db\Exception
     */
    public function safeUp()
    {
        foreach ($this->_rows as $row) {
            $model = new Setting();

            $model->setAttributes([
                'section' => $row['section'],
                'key' => $row['key'],
            ]);

            $model->name = isset($row['name']) ? $row['name'] : $row['key'];
            $model->hint = isset($row['hint']) ? $row['hint'] : null;
            $model->value = isset($row['value']) ? (string)$row['value'] : '';
            $model->position = isset($row['position']) ? $row['position'] : null;
            $model->type_key = isset($row['type_key']) ? $row['type_key'] : $model::TYPE_TEXT;
            $model->variants = isset($row['variants']) ? $row['variants'] : ['safe'];
            $model->rules = isset($row['rules']) ? $row['rules'] : ['safe'];

            if ($model->validate()) {
                $tableName = $model::tableName();

                $query = (new Query())
                    ->select('*')
                    ->from($tableName)
                    ->where([
                        'section' => $model->section,
                        'position' => $model->position
                    ]);

                if ($query->count()) {
                    $command = $this->db->createCommand()
                        ->update(
                            $tableName,
                            ['position' => new Expression('position+1')],
                            'position >= :position AND section = :section',
                            ['position' => $model->position, 'section' => $model->section]
                        );

                    if (!$command->execute()) {
                        echo "Saving error\n";
                        return false;
                    }
                }

                if (!$model->save()) {
                    $error = ['Save Error!!'];
                    $error['attributes'] = $model->getAttributes();
                    $error['errors'] = $model->errors;

                    print_r($error);
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Migration down
     * @return bool
     */
    public function safeDown()
    {
        foreach ($this->_rows as $row) {
            $command = $this->db
                ->createCommand()
                ->delete(
                    Setting::tableName(),
                    [
                        'section' => $row['section'],
                        'key' => $row['key']
                    ]
                );

            if (!$command->execute()) {
                echo "Error, position not updated!\n";
                return false;
            }
        }

        return true;
    }
}