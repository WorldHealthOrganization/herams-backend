<?php

namespace prime\controllers;

use prime\components\Controller;
use prime\interfaces\ReportGeneratorInterface;
use prime\interfaces\ReportInterface;
use prime\models\ar\Project;
use prime\models\ar\Report;
use prime\models\ar\Tool;
use prime\models\ar\UserData;
use prime\reportGenerators\Test;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\NotFoundHttpException;
use yii\web\Request;
use yii\web\Response;
use yii\web\Session;
use yii\web\User;

class ReportsController extends Controller
{
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(),
            [
                'access' => [
                    'rules' => [

                    ]
                ]
            ]
        );
    }

    public function actionPreview(
        Request $request,
        Response $response,
        $projectId, $reportGenerator)
    {
        /* @todo set correct privilege */
        $project = Project::loadOne($projectId);
        if(isset(Tool::generatorOptions()[$reportGenerator])) {
            if($request->isPost) {
                $userData = $project->getUserData($reportGenerator)->one();
                if(!isset($userData)) {
                    $userData = new UserData();
                }
                $requestData = [
                    'project_id' => $projectId,
                    'generator' => $reportGenerator,
                    'data' => $request->post()
                ];
                $userData->setAttributes($requestData);

                $response->format = Response::FORMAT_JSON;
                if($userData->save()) {
                    return [];
                } else {
                    $response->setStatusCode(422);
                    return $userData->errors;
                }
            }

            return $this->render('preview', [
                'previewUrl' => Url::toRoute(['reports/render-preview', 'projectId' => $projectId, 'reportGenerator' => $reportGenerator]),
                'projectId' => $projectId,
                'reportGenerator' => $reportGenerator
            ]);
        } else {
            throw new NotFoundHttpException(\Yii::t('app', '{reportGenerator} does not exist', ['reportGenerator' => $reportGenerator]));
        }
    }

    public function actionRead(Response $response, $id)
    {
        $report = Report::loadOne($id);
        $response->setContentType($report->getMimeType());
        $response->content = $report->getStream();
        return $response;
    }

    public function actionRenderFinal(
        User $user,
        Response $response,
        $projectId, $reportGenerator)
    {
        /* @todo set correct privilege */
        $project = Project::loadOne($projectId);
        if(isset(Tool::generatorOptions()[$reportGenerator])) {
            $userData = $project->getUserData($reportGenerator)->one();
            if(!isset($userData)) {
                $userData = new UserData();
            }
            $generator = Tool::generatorOptions()[$reportGenerator];
            /** @var ReportGeneratorInterface $generator */
            $generator = new $generator;
            /** @var ReportInterface $report */
            $report = $generator->render(
                $project->getResponses(),
                $user->identity->createSignature(),
                $userData
            );

            $response->setContentType($report->getMimeType());
            $response->content = $report->getStream();
            return $response;
        } else {
            throw new NotFoundHttpException(\Yii::t('app', '{reportGenerator} does not exist', ['reportGenerator' => $reportGenerator]));
        }
    }

    public function actionRenderPreview(
        User $user,
        $projectId, $reportGenerator)
    {
        /* @todo set correct privilege */
        $project = Project::loadOne($projectId);
        if(isset(Tool::generatorOptions()[$reportGenerator])) {
            $userData = $project->getUserData($reportGenerator)->one();
            if(!isset($userData)) {
                $userData = new UserData();
            }
            $generator = Tool::generatorOptions()[$reportGenerator];
            /** @var ReportGeneratorInterface $generator */
            $generator = new $generator;
            return $generator->renderPreview(
                $project->getResponses(),
                $user->identity->createSignature(),
                $userData
            );
        } else {
            throw new NotFoundHttpException(\Yii::t('app', '{reportGenerator} does not exist', ['reportGenerator' => $reportGenerator]));
        }
    }

    public function actionPublish(
        User $user,
        Session $session,
        Request $request,
        $projectId, $reportGenerator)
    {
        /* @todo set correct privilege */
        $project = Project::loadOne($projectId);
        if(isset(Tool::generatorOptions()[$reportGenerator])) {
            if($request->isPost) {
                $userData = $project->getUserData($reportGenerator)->one();
                if (!isset($userData)) {
                    $userData = new UserData();
                }
                $generator = Tool::generatorOptions()[$reportGenerator];
                /** @var ReportGeneratorInterface $generator */
                $generator = new $generator;
                $report = Report::saveReport(
                    $generator->render(
                        $project->getResponses(),
                        $user->identity->createSignature(),
                        $userData
                    ),
                    $projectId,
                    $reportGenerator
                );

                if(isset($report)) {
                    $session->setFlash(
                        'reportPublished',
                        [
                            'type' => \kartik\widgets\Growl::TYPE_SUCCESS,
                            'text' => "Report for <strong>{$project->title}</strong> is published.",
                            'icon' => 'glyphicon glyphicon-ok'
                        ]
                    );
                } else {
                    $session->setFlash(
                        'reportPublished',
                        [
                            'type' => \kartik\widgets\Growl::TYPE_DANGER,
                            'text' => "Report for <strong>{$project->title}</strong> was not published! Try again.",
                            'icon' => 'glyphicon glyphicon-remove'
                        ]
                    );
                }

                return $this->redirect(
                    [
                        'projects/read',
                        'id' => $projectId
                    ]
                );

            }
            return $this->render('publish', [
                'finalUrl' => Url::toRoute(['reports/render-final', 'projectId' => $projectId, 'reportGenerator' => $reportGenerator]),
                'projectId' => $projectId,
                'reportGenerator' => $reportGenerator
            ]);
        }
    }
}