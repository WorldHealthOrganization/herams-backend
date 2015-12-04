<?php

namespace prime\models\search;

use prime\components\ActiveQuery;
use prime\models\ar\Tool;
use prime\models\Country;
use yii\db\Expression;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use yii\validators\ExistValidator;
use yii\validators\NumberValidator;
use yii\validators\RangeValidator;
use yii\validators\StringValidator;

class Project extends \prime\models\ar\Project
{
    /** @var ActiveQuery */
    public $query;

    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'countryIds' => \Yii::t('app', 'Tool'),
        ]);
    }


    public function countriesOptions()
    {
        return ArrayHelper::map(
            array_filter($this->query->copy()->select('country_iso_3')->distinct()->column()),
            function($model) {
                return $model;
            },
            function($model) {
                return Country::findOne($model)->name;
            }
        );
    }

    public function init()
    {
        parent::init();
        $this->scenario = 'search';

        $this->query = \prime\models\ar\Project::find()->notClosed();
        $this->query->joinWith(['tool']);
    }

    public function rules()
    {
        return [
            [['created'], 'safe'],
            [['tool_id'], RangeValidator::class, 'range' => array_keys($this->toolsOptions()), 'allowArray' => true],
            [['title', 'description', 'tool_id'], StringValidator::class],

        ];
    }

    public function save($runValidation = true, $attributeNames = null)
    {
        throw new \Exception('You cannot save a search model');
    }

    public function scenarios()
    {
        return [
            'search' => [
                'tool_id',
                'country_iso_3',
                'title',
                'description',
                'created'
            ]
        ];
    }

    public function search($params)
    {
        $dataProvider = new ActiveDataProvider([
            'query' => $this->query,
            'id' => 'project-data-provider'
        ]);


        /**
         * To allow sorting by country (countries are not in the database) we construct a case statement.
         * This is not ideal from a database perspective, but for this case (there won't be many projects), it's ok.
         */
        $countries = Country::findAll();
        ArrayHelper::multisort($countries, 'name');
        $case = '(case ';
        foreach ($countries as $key => $value) {
            $case .= "when country_iso_3 = '{$value->iso_3}' then $key ";
        }
        $case .= ' end)';


        $dataProvider->setSort([
            'attributes' => [
                'title',
                'description',
                'tool_id' => [
                    'asc' => ['tool.acronym' => SORT_ASC],
                    'desc' => ['tool.acronym' => SORT_DESC],
                    'default' => 'asc'
                ],
                'country_iso_3' => [
                    'asc' => [$case => SORT_ASC],
                    'desc' => [$case => SORT_DESC],
                    'default' => 'asc'
                ],
                'created'
            ]
        ]);

        if(!$this->load($params) || !$this->validate()) {
            return $dataProvider;
        }

        $interval = explode(' - ', $this->created);
        if(count($interval) == 2) {
            $this->query->andFilterWhere([
                'and',
                ['>=', 'created', $interval[0]],
                ['<=', 'created', $interval[1]]
            ]);
        }

        $this->query->andFilterWhere(['tool_id' => $this->tool_id]);
        $this->query->andFilterWhere(['country_iso_3' => $this->country_iso_3]);
        $this->query->andFilterWhere(['like', \prime\models\ar\Project::tableName() . '.title', $this->title]);
        $this->query->andFilterWhere(['like', \prime\models\ar\Project::tableName() . '.description', $this->description]);

        return $dataProvider;
    }

    public function toolsOptions()
    {
        return ArrayHelper::map(
            $this->query->copy()->orderBy(Tool::tableName() . '.title')->all(),
            'tool.id',
            'tool.acronym'
        );
    }

}