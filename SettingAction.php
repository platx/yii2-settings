<?php

namespace platx\settings;

use Yii;
use yii\base\Action;
use yii\web\NotFoundHttpException;


/**
 * Action for updating section settings
 * @package platx\settings
 */
class SettingAction extends Action
{
    /**
     * @return string
     * @throws NotFoundHttpException
     */
    public function run()
    {
        $section = Yii::$app->request->get('section');

        $settingForm = new SettingForm();
        if (!$settingForm->loadBySection($section)) {
            throw new NotFoundHttpException('Section not found!');
        }

        $post = Yii::$app->request->post();

        if ($post) {
            $settingForm->load($post, 'SettingForm');

            if ($settingForm->save()) {
                if (!empty(Yii::$app->session)) {
                    Yii::$app->session->setFlash('success', 'Saved');
                }
            } else {
                if (!empty(Yii::$app->session)) {
                    Yii::$app->session->setFlash('error', 'Save error!');
                }
            }
        }

        return Yii::$app->view->render('index', [
            'settingForm' => $settingForm
        ]);
    }
}