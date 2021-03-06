<?php
declare(strict_types=1);

use kartik\grid\GridView;
use prime\helpers\Icon;
use prime\models\ar\Permission;
use prime\models\ar\Project;
use prime\models\ar\User;
use prime\models\search\Workspace;
use prime\widgets\DateTimeColumn;
use prime\widgets\DrilldownColumn;
use prime\widgets\FavoriteColumn\FavoriteColumn;
use prime\widgets\IdColumn;
use prime\widgets\menu\ProjectTabMenu;
use prime\widgets\Section;
use SamIT\abac\interfaces\Resolver;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use yii\web\View;

/**
 * @var ActiveDataProvider $workspaceProvider
 * @var Workspace $workspaceSearch
 * @var int $closedCount
 * @var View $this
 * @var Project $project
 * @var Resolver $abacResolver
 */

$this->title = $project->title;
$this->beginBlock('tabs');
echo ProjectTabMenu::widget(
    ['project' => $project]
);
$this->endBlock();

Section::begin(
    [
        'subject' => $project,
        'actions' => [
            [
                'icon'       => Icon::add(),
                'label'      => \Yii::t('app', 'Create workspace'),
                'link'       => [
                    'workspace/create', 'project_id' => $project->id
                ],
                'permission' => Permission::PERMISSION_MANAGE_WORKSPACES,
            ],
            [
                'icon'       => Icon::download_1(),
                'label'      => \Yii::t('app', 'Import workspaces'),
                'link'       => [
                    'workspace/import', 'project_id' => $project->id
                ],
                'permission' => Permission::PERMISSION_MANAGE_WORKSPACES,
            ],
        ],
    ]
);
echo GridView::widget(
    [
        'pjax'         => true,
        'export'       => false,
        'pjaxSettings' => [
            'options' => [
                // Just links in the header.
                'linkSelector' => 'th a',
            ],
        ],
        'filterModel'  => $workspaceSearch,
        'dataProvider' => $workspaceProvider,
        'columns'      => [
            [
                'class'  => FavoriteColumn::class,

                'filter' => [
                    "1" => \Yii::t('app', 'Favorites only'),
                    "0" => \Yii::t('app', 'Non-favorites only'),
                ],
            ],
            [
                'class' => IdColumn::class,
            ],
            [
                'class'      => DrilldownColumn::class,
                'attribute'  => 'title',
                'permission' => Permission::PERMISSION_LIST_FACILITIES,
                'link'       => function ($workspace) {
                    return [
                        'workspace/limesurvey', 'id' => $workspace->id
                    ];
                },
            ],
            [
                'attribute' => 'latestUpdate',
                'class'     => DateTimeColumn::class,
            ],
            ['attribute' => 'contributorCount'],
            ['attribute' => 'facilityCount'],
            ['attribute' => 'responseCount'],
            [
                'label' => \Yii::t('app', 'Workspace owner'),
                'value' => static function (\prime\models\ar\Workspace $workspace) {
                    $usersQuery = $workspace->getLeads();
                    return implode('<br>', ArrayHelper::getColumn($usersQuery->all(), 'name'));
                },
                'format' => 'html',
            ]
        ],
    ]
);

Section::end();
