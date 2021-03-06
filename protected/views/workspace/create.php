<?php
declare(strict_types=1);

use app\components\ActiveForm;
use app\components\Form;
use kartik\select2\Select2;
use prime\models\ar\Workspace;
use prime\widgets\ButtonGroup;
use prime\widgets\FormButtonsWidget;
use prime\widgets\Section;
use yii\web\View;

/**
 * @var View $this
 * @var Workspace $model
 */

$this->params['breadcrumbs'][] = [
    'label' => $model->project->title,
    'url' => ['project/workspaces', 'id' => $model->project->id]
];

$this->title = \Yii::t('app', 'New workspace');

Section::begin()
    ->withHeader($this->title);

$form = ActiveForm::begin();

echo Form::widget([
    'form' => $form,
    'model' => $model,
    'columns' => 1,
    "attributes" => [
        'title' => [
            'type' => Form::INPUT_TEXT,
        ],
        'token' => [
            'type' => Form::INPUT_WIDGET,
            'widgetClass' => Select2::class,
            'options' => [
                'data' => $model->tokenOptions(),
            ],
        ],
        FormButtonsWidget::embed([
            'buttons' => [
                ['label' => \Yii::t('app', 'Create workspace'), 'type' => ButtonGroup::TYPE_SUBMIT, 'style' => 'primary'],
            ]
        ])

    ]
]);

ActiveForm::end();

Section::end();
