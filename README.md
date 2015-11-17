Yii2 Settings
=============
DB settings for Yii2.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist platx/yii2-settings "*"
```

or add

```
"platx/yii2-settings": "*"
```

to the require section of your `composer.json` file.


Usage
-----

For insert new settings, create migration, that extends from \platx\settings\SettingsMigration and implements parameter protected $_rows, like this:

```php
protected $_rows = [
   [
      self::FIELD_SECTION => 'general',
      self::FIELD_KEY => 'string_setting',
      self::FIELD_NAME => 'String setting',
      self::FIELD_HINT => 'Text setting with max value = 255',
      self::FIELD_VALUE => 'Here some default value of setting',
      self::FIELD_TYPE => Setting::TYPE_TEXT,
      self::FIELD_RULES => [
          ['string', 'max' => 255],
      ]
   ],
   [
      self::FIELD_SECTION => 'general',
      self::FIELD_KEY => 'integer_setting',
      self::FIELD_NAME => 'Integer setting',
      self::FIELD_HINT => 'Setting with integer value',
      self::FIELD_VALUE => '235',
      self::FIELD_TYPE => Setting::TYPE_TEXT,
      self::FIELD_RULES => [
          ['integer', 'max' => 500],
      ]
   ],
   [
      self::FIELD_SECTION => 'general',
      self::FIELD_KEY => 'text_setting',
      self::FIELD_NAME => 'Text setting',
      self::FIELD_HINT => 'Setting with big text value',
      self::FIELD_VALUE => 'some big text',
      self::FIELD_TYPE => Setting::TYPE_TEXTAREA,
      self::FIELD_RULES => [
          ['string'],
      ]
   ],
   [
      self::FIELD_SECTION => 'general',
      self::FIELD_KEY => 'checkbox_setting',
      self::FIELD_NAME => 'Checkbox setting',
      self::FIELD_HINT => 'Checkbox setting with true or false condition',
      self::FIELD_VALUE => '0',
      self::FIELD_TYPE => Setting::TYPE_CHECKBOX,
      self::FIELD_RULES => [
          ['integer'],
      ]
   ],
];
```

For use setting value in your code, try:

```php
$settingValue = \platx\settings\Setting::get('section.key');
```

Where `section` - Setting section, `key` - setting key.

For update setting value, use following code:

```php
\platx\settings\Setting::set('section.key', 'Here put new value for setting');
```

For update settings for all section, use `\platx\settings\SettingForm` class. Also you can use in your admin controller action
`platx\settings\SettingAction` and in your view file you can do something like this:

```php
<?php $form = ActiveForm::begin([
    'options' => ['class' => 'form-horizontal'],
    'fieldConfig' => [
        'template' => '{label}<div class="col-sm-10">{input}{error}{hint}</div>',
        'labelOptions' => ['class' => 'col-sm-2 control-label'],
    ],
]); ?>
    <?php foreach ($settingForm->getSettings() as $key => $settingModel) : ?>
        <?php
            if ($settingModel->type_key == $settingModel::TYPE_TEXT) {
                $field = $form->field($settingForm, $key);
            }
            if ($settingModel->type_key == $settingModel::TYPE_TEXTAREA) {
                $field = $form->field($settingForm, $key)->textarea(['rows' => 8]);
            }
            if ($settingModel->type_key == $settingModel::TYPE_EDITOR) {
                $field = $form->field($settingForm, $key)->widget(CKEditor::className(), [
                    'options' => ['rows' => 6],
                    'preset' => 'full'
                ]);
            }
            if ($settingModel->type_key == $settingModel::TYPE_SELECTBOX) {
                $field = $form->field($settingForm, $key)->dropDownList($settingModel->getVariants());
            }
            if ($settingModel->type_key == $settingModel::TYPE_SELECTBOX_MULTIPLE) {
                $field = $form->field($settingForm, $key)->dropDownList($settingModel->getVariants(),
                    ['multiple' => true]);
            }
            if ($settingModel->type_key == $settingModel::TYPE_CHECKBOX) {
                $field = $form->field($settingForm, $key)->checkbox();
            }
            if ($settingModel->type_key == $settingModel::TYPE_RADIO) {
                $field = $form->field($settingForm, $key)->radio();
            }
            if ($settingModel->type_key == $settingModel::TYPE_RADIOLIST) {
                $field = $form->field($settingForm, $key)->radioList($settingModel->getVariants());
            }
            echo $field->hint($settingModel->hint);
        ?>
    <?php endforeach; ?>
<?= Html::submitButton('Save', ['class' => 'btn btn-primary']) ?>
<?php $form->end(); ?>
```

