<?php

namespace platx\settings;

use yii\db\Migration;


/**
 * Creates setting table
 * @package platx\settings
 */
class CreateSettingTableMigration extends Migration
{
    /**
     * @var string Table name for migrate
     */
    protected $_tableName = '{{%setting}}';

    /**
     * @var string Table options for migrate
     */
    protected $_tableOptions;

    /**
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        if (is_null($this->_tableName)) {
            throw new \yii\base\InvalidConfigException('$_tableName must be set!');
        }

        if ($this->db->driverName === 'mysql' && $this->_tableOptions !== false) {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $this->_tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        parent::init();
    }

    /**
     * Migration Up
     */
    public function safeUp()
    {
        $this->createTable($this->_tableName, [
            'section' => $this->string(50)->notNull(),
            'key' => $this->string(50)->notNull(),
            'name' => $this->string(100)->notNull(),
            'hint' => $this->string(),
            'value' => $this->text(),
            'type_key' => $this->smallInteger(2)->notNull()->defaultValue(0),
            'position' => $this->integer()->notNull()->defaultValue(1),
            'variants' => $this->text(),
            'rules' => $this->text(),
        ], $this->_tableOptions);

        $this->addPrimaryKey('setting_pk1', $this->_tableName, ['section', 'key']);
    }

    /**
     * Migration down
     */
    public function safeDown()
    {
        $this->dropTable($this->_tableName);
    }
}