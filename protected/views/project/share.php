<?php
declare(strict_types=1);

use app\components\ActiveForm;
use prime\components\View;
use prime\models\ar\Project;
use prime\models\forms\Share;
use prime\widgets\menu\ProjectTabMenu;
use prime\widgets\Section;

/**
 * @var Project $project
 * @var View $this
 * @var Share $model
 */

$this->params['breadcrumbs'][] = [
    'label' => $project->title,
    'url' => ['project/workspaces', 'id' => $project->id]
];
$this->title = $project->title;

$this->params['subtitle'] = \Yii::t('app', 'Add new user');
$this->beginBlock('tabs');
echo ProjectTabMenu::widget([
    'project' => $project,
]);
$this->endBlock();

Section::begin()
    ->withHeader(\Yii::t('app', 'Add new user'));

$form = ActiveForm::begin([
    "type" => ActiveForm::TYPE_HORIZONTAL,
    'formConfig' => [
        'labelSpan' => 3

    ]
]);
echo $model->renderForm($form);
$form->end();

Section::end();

Section::begin()
    ->withHeader(\Yii::t('app', 'View current users'));

echo $model->renderTable();

Section::end();
