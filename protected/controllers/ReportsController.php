<?php

namespace prime\controllers;

use prime\components\Controller;
use prime\factories\GeneratorFactory;
use prime\interfaces\ReportGeneratorInterface;
use prime\interfaces\ReportInterface;
use prime\models\ar\Project;
use prime\models\ar\Report;
use prime\models\ar\UserData;
use prime\objects\ResponseCollection;
use prime\objects\SurveyCollection;
use SamIT\LimeSurvey\Interfaces\ResponseInterface;
use SamIT\LimeSurvey\Interfaces\SurveyInterface;
use SamIT\LimeSurvey\JsonRpc\Client;
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
        $projectId,
        $reportGenerator
    )
    {
        /* @todo set correct privilege */
        $project = Project::loadOne($projectId);
        if(isset(GeneratorFactory::classes()[$reportGenerator])) {
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
                'project' => $project,
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
        if(isset(GeneratorFactory::classes()[$reportGenerator])) {
            $userData = $project->getUserData($reportGenerator)->one();
            if(!isset($userData)) {
                $userData = new UserData();
            }
            $generator = GeneratorFactory::classes()[$reportGenerator];
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
        $projectId,
        $reportGenerator,
        Client $limesurvey
    )
    {
        /* @todo set correct privilege */
        $project = Project::loadOne($projectId);
        if(isset(GeneratorFactory::classes()[$reportGenerator])) {
            $userData = $project->getUserData($reportGenerator)->one();
            if(!isset($userData)) {
                $userData = new UserData();
            }
            /** @var ReportGeneratorInterface $generator */
            $generator = GeneratorFactory::get($reportGenerator);

            $surveys = new SurveyCollection();
            $surveys->append($limesurvey->getSurvey(22814, 'en'));
            $surveys->append($limesurvey->getSurvey(67825, 'en'));

            $responses = new ResponseCollection();
            /** @var SurveyInterface $survey */
            foreach($surveys as $survey) {
                /** @var ResponseInterface $response */
                foreach ($limesurvey->getResponses($survey->getId()) as $response) {
                    if($response->getData()['token'] == '3zhvuud5f88hkui') {
                        $responses->append($response);
                    }
                }
            }

            return $generator->renderPreview(
                $responses,
                $surveys,
                $user->identity->createSignature(),
                $project,
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
        if(isset(GeneratorFactory::classes()[$reportGenerator])) {
            if($request->isPost) {
                $userData = $project->getUserData($reportGenerator)->one();
                if (!isset($userData)) {
                    $userData = new UserData();
                }
                $generator = GeneratorFactory::classes()[$reportGenerator];
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