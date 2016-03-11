<?php


namespace prime\api;

use \Yii;
use yii\base\Module;
use yii\log\FileTarget;
use yii\web\Response;

class Api extends Module
{
    /**
     * The previously set exception handler.
     * @var Callable
     */
    protected $oldHandler;

    public function init()
    {
        parent::init();
        $this->module->response->getHeaders()->add('Access-Control-Allow-Origin', '*');
        $this->module->log->targets['error'] = new FileTarget([
            'logFile' => '@runtime/logs/api-error.log',
            'levels' => ['warning', 'error', 'info']
        ]);
    }
}