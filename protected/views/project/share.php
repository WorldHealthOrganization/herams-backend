<?php

use app\components\ActiveForm;
use app\components\Form;
use prime\widgets\FormButtonsWidget;
use yii\helpers\Html;

/**
 * @var \prime\models\ar\Project $project
 * @var \prime\models\forms\Share $model
 */

$this->params['breadcrumbs'][] = [
    'label' => \Yii::t('app', 'Admin dashboard'),
    'url' => ['/admin']
];
$this->params['breadcrumbs'][] = [
    'label' => \Yii::t('app', 'Projects'),
    'url' => ['/project']
];

$this->title = \Yii::t('app', 'Manage user permissions for {project}', ['project' => $project->title]);
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="col-xs-12 share-form">
    <div class="col-xs-12 col-md-8 col-lg-6 permissions-form form-bg">
        <?php
        echo Html::tag('h2', \Yii::t('app', 'Add permissions'));
        $form = ActiveForm::begin([
            "type" => ActiveForm::TYPE_HORIZONTAL,
            'formConfig' => [
                'showLabels' => true,
                'defaultPlaceholder' => false

            ]
        ]);
        echo $model->renderForm($form);
        echo Form::widget([
            'form' => $form,
            'model' => $model,
            'attributes' => [
                FormButtonsWidget::embed([
                    'buttons' => [
                        ['label' => \Yii::t('app', 'Add'), 'options' => ['class' => ['btn', 'btn-primary']]]
                    ]
                ])
            ]
        ]);
        $form->end();
        ?>
    </div>
    <div class="col-xs-12 list-shared form-bg">
        <h2><?= \Yii::t('app', 'View user permissions') ?></h2>
        <?php
        echo $model->renderTable();
        ?>
    </div>
</div>