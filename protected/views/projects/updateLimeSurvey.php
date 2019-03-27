<?php

use yii\bootstrap\Html;

/**
 * @var \yii\web\View $this
 * @var \prime\models\ar\Workspace $model
 */

//$this->registerAssetBundle(\prime\assets\ReportResizeAsset::class);
$this->params['breadcrumbs'][] = [
    'label' => \Yii::t('app', 'Back to project overview'),
    'url' => ['projects/overview', 'pid' => $model->tool_id]
];
$this->params['breadcrumbs'][] = [
    'label' => \Yii::t('app', 'Manage workspaces'),
    'url' => ['projects/list', 'toolId' => $model->tool_id]
];

$this->title = \Yii::t('app', 'Update data');
echo Html::beginTag('div', [
    'class' => ['full-page']
]);
echo Html::tag('iframe', '', [
    'src' => $model->getSurveyUrl(),
    'class' => [],
    'style' => [
        //'height' => '800px'
    ]
]);
echo Html::endTag('div');