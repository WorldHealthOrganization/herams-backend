<?php

use app\components\Html;
use \prime\models\permissions\Permission;
use prime\models\ar\Setting;
use \yii\helpers\Url;

/**
 * @var \prime\models\ar\Project $model
 * @var \yii\web\View $this
 * @var \yii\data\ArrayDataProvider $responsesDataProvider
 */
$this->params['containerOptions'] = ['class' => 'container-fluid'];
$this->params['subMenu']['items'] = [];

$this->params['subMenu']['items'][] = [
    'label' => Html::icon(Setting::get('icons.explore', 'tint')),
    'url' => ['/projects/explore', 'id' => $model->id],//$model->surveyUrl,
    'visible' => $model->userCan(Permission::PERMISSION_READ, app()->user->identity) && $model->closed === null,
    'options' => [
        'class' => 'icon',
        'title' => \Yii::t('app', 'Explore data'),
    ],
];

$this->params['subMenu']['items'][] = [
    'label' => Html::icon(Setting::get('icons.limeSurveyUpdate')),
    'url' => ['/projects/update-lime-survey', 'id' => $model->id],//$model->surveyUrl,
    'visible' => $model->userCan(Permission::PERMISSION_WRITE, app()->user->identity) && $model->closed === null,
    'options' => [
        'class' => 'icon',
        'title' => \Yii::t('app', 'Data update'),
    ],
];

if(isset($model->defaultGenerator)) {
    $this->params['subMenu']['items'][] = [
        'label' => Html::icon(Setting::get('icons.preview')),
        'options' => [
            'class' => 'icon',
            'title' => \Yii::t('app', 'Preview report'),
        ],
        'url' => [
            '/reports/publish',
            'projectId' => $model->id,
            'reportGenerator' => $model->default_generator
        ],
        'visible' => $model->userCan(Permission::PERMISSION_WRITE, app()->user->identity)
            && ($model->getResponses()->size() > 0)
            && $model->closed === null
    ];
}

$this->params['subMenu']['items'][] = [
    'label' => Html::icon(Setting::get('icons.share')),
    'url' => ['/projects/share', 'id' => $model->id],
    'visible' => $model->userCan(Permission::PERMISSION_SHARE, app()->user->identity) && $model->closed === null,
    'options' => [
        'class' => 'icon',
        'title' => \Yii::t('app', 'Share'),
    ],
];

$this->params['subMenu']['items'][] = [
    'label' => Html::icon(Setting::get('icons.download', 'download-alt')),
    'url' => '#',
    'visible' => $model->userCan(Permission::PERMISSION_ADMIN, app()->user->identity) && $model->getResponses()->size() > 0,
    'options' => [
        'download' => true,
        'class' => 'icon',
        'id' => 'download-data',
        'title' => \Yii::t('app', 'Download'),
    ],
];

$codeUrl = json_encode(\yii\helpers\Url::to(['/projects/download', 'id' => $model->id]));
$textUrl = json_encode(\yii\helpers\Url::to(['/projects/download', 'id' => $model->id, 'text' => true]));
$this->registerJs(<<<SCRIPT
var handler = function(e){
    console.log('Clicked');
    e.preventDefault();
    e.stopPropagation();
    bootbox.dialog({
        message: "Do you prefer answer as text or as code?",
        title: "Download data in CSV format",
        onEscape: function() {
        },
        buttons: {
            text: {
                label: "Text",
                callback: function() {
                    window.location.href = $textUrl;
                }
            },
            code: {
                label: "Code",
                callback: function() {
                    window.location.href = $codeUrl;
                }
            },
        }
    
    });
};
$('#download-data').on('click', handler);
SCRIPT
);


$this->params['subMenu']['items'][] = [
    'label' => Html::icon(Setting::get('icons.close')),
    'url' => ['/projects/close', 'id' => $model->id],
    'options' => [
        'class' => 'icon',
        'title' => \Yii::t('app', 'Deactivate'),
    ],
    'linkOptions' => [
        'data-confirm' => \Yii::t('app', 'Are you sure you want to close project <strong>{modelName}</strong>?', ['modelName' => $model->title]),
        'data-method' => 'delete'
    ],
    'visible' => $model->userCan(Permission::PERMISSION_ADMIN, app()->user->identity) && $model->closed === null
];

$this->params['subMenu']['items'][] = [
    'label' => Html::icon(Setting::get('icons.open')),
    'url' => ['/projects/re-open', 'id' => $model->id],
    'options' => [
        'class' => 'icon',
        'title' => \Yii::t('app', 'Reactivate'),
    ],
    'linkOptions' => [
        'data-confirm' => \Yii::t('app', 'Are you sure you want to re-open project <strong>{modelName}</strong>?', ['modelName' => $model->title]),
        'data-method' => 'put'
    ],
    'visible' => $model->userCan(Permission::PERMISSION_ADMIN, app()->user->identity) && $model->closed !== null
];

?>


<div class="col-xs-12">
    <?php

    $this->registerAssetBundle(\prime\assets\ReportResizeAsset::class);
    /**
     * @todo Add proper auth
     */
    $config = [
        "responseUrl" => 'https://localhost' . Url::toRoute(['api/collections/view', 'id' => $model->id, '_format' => 'json'], false),
        "structureUrl" => 'https://localhost' . Url::toRoute(['api/surveys/view', 'id' => $model->data_survey_eid, '_format' => 'json'], false),
        "seriesColumn" => null,
        "filterExpression" => "CM.*",
        "token" => (string)$token
    ];

    echo Html::tag('iframe', '', [
        'src' => 'https://explore.primewho.org#' . json_encode($config),
            'style' => [
                'display' => 'block',
                'height' => '800px',
                'border' => 'none'
            ]
        ]);
    ?>
    <script>
        (function(iframe) {
            iframe .style.height = window.innerHeight - 160 + 'px';
            document.addEventListener('onResize', function () {
                iframe.style.height = window.innerHeight - 160 + 'px';
            })
        })(document.getElementsByTagName('iframe')[0]);
    </script>
</div>

