<?php

namespace app\models;

use app\components\Api;
use app\components\apiDataException;
use app\components\HPlan;
use app\models\tenderModels\Document;
use Yii;
use yii\base\ErrorException;
use app\components\ApiHelper;
use app\models\planModels\Plan;
use yii\base\Exception;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii\db\Query;
use yii\web\NotFoundHttpException;

/**
 * This is the model class for table "plans".
 *
 * @property integer $id
 * @property integer $user_id
 * @property integer $company_id
 * @property string $title
 * @property string $description
 * @property string $status
 * @property integer $created_at
 * @property integer $update_at
 * @property string $json
 * @property string $signed_data
 * @property string $response
 * @property string $token
 * @property string $transfer_token
 * @property string $plan_id
 * @property string $plan_cbd_id
 * @property string $date_modified
 * @property string $code_cpv
 * @property integer $ecp
 * @property boolean $is_cbd_deleted
 *
 * @property User $user
 */
class Plans extends \yii\db\ActiveRecord
{

    public $data = '';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'plans';
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'update_at',
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id'], 'integer'],
            [['json', 'response', 'signed_data', 'code_cpv'], 'string'],
            [['token', 'transfer_token', 'plan_id'], 'safe'],
            [['title', 'status', 'date_modified'], 'string', 'max' => 255],
            ['plan_cbd_id', 'string', 'max' => 32],
            [['created_at', 'update_at', 'ecp'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_id' => Yii::t('app', 'User ID'),
            'title' => Yii::t('app', 'Title'),
            'status' => Yii::t('app', 'Status'),
            'created_at' => Yii::t('app', 'Created At'),
            'update_at' => Yii::t('app', 'Update At'),
            'json' => Yii::t('app', 'Json'),
            'response' => Yii::t('app', 'Response'),
            'token' => Yii::t('app', 'Token'),
            'transfer_token' => Yii::t('app', 'transfer_token'),
            'plan_id' => Yii::t('app', 'plan_id'),
            'plan_cbd_id' => Yii::t('app', 'PlanID'),
            'ecp' => Yii::t('app', 'ecp'),
        ];
    }

    public static function getPlans($id)
    {
        //$plans = Plans::find('response')->where(['id' => $id])->limit(1)->asArray()->one();
        $plans = self::getModel($id);
        $plans->json = json_decode($plans->json, 1);
        if ($plans->response) {
            $json = json_decode($plans->response, 1);
            $json = ['Plan' => $json['data']];
            //$json = [ 'Plan' => json_decode($plans->response, 1)['data'] ];
            ApiHelper::FormatDate($json['Plan']);
            Document::setVersionOfAllDocuments($json['Plan']);
            $plans->json = $json;
        }
        return $plans;
    }

    public function beforeSave($insert)
    {
        if ($this->isNewRecord) {
            if (is_a(Yii::$app, 'yii\web\Application')) {// если не консоль
                $this->user_id = Yii::$app->user->id;
                $this->company_id = Yii::$app->user->identity->company_id;
            }
        }

        return parent::beforeSave($insert);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTender()
    {
        return $this->hasMany(Tenders::class, ['tender_id' => 'tender_id'])
            ->viaTable('plan_tender', ['plan_id' => 'plan_id']);
    }

    /**
     * @param $id
     *
     * @return Plans
     * @throws NotFoundHttpException
     */
    public static function getModel($id)
    {
        $plans = Plans::find()->where(['id' => $id])->limit(1)->one();
        if (!$plans) {
            if (Yii::$app->params['elastic']) {
                if ($res = \app\models\elasticModels\Plans::findOne($id)) {
                    $res->delete();
                }
            }
            throw new NotFoundHttpException(Yii::t('app', 'plan.not.found'));
        }
        return $plans;
    }


    /**
     * @param $plans Plans
     * @param $plan  Plan
     * @param $post  array
     * @param bool|false $validate
     *
     * @throws ErrorException
     * @return bool
     */
    public static function submitToDB($plans, $plan, &$post, $validate = false)
    {
        if (empty($post['Plan']['rationale']['description'])){
            unset($post['Plan']['rationale']);
        }

        // Delete empty item template
        unset($post['Plan']['items']['__EMPTY_ITEM__']);

        // Resort items
        $post['Plan']['items'] = array_values($post['Plan']['items']);

        //procurementMethod
        if ($post['procurementMethod'] == '') {
            $post['Plan']['tender']['procurementMethod'] = '';
            $post['Plan']['tender']['procurementMethodType'] = '';
        } else {
            $method = explode('_', $post['procurementMethod']);
            $post['Plan']['tender']['procurementMethod'] = $method[0];
            $post['Plan']['tender']['procurementMethodType'] = $method[1];
        }

        if ($post['Plan']['tender']['tenderPeriod']['startDate'] != '' && $post['Plan']['tender']['procurementMethodType'] != 'centralizedProcurement') {
            $post['Plan']['tender']['tenderPeriod']['startDate'] = '01/' . $post['Plan']['tender']['tenderPeriod']['startDate'];
        }

        //формуємо вміст частини Buyers та procuringEntity в моделі Plan
        $post['Plan']['buyers'][0] = array_merge(ApiHelper::fillCompany(), $post['Plan']['buyers'][0]);

        foreach ($post['Plan']['buyers'] as $k => $buyer){
            foreach ($buyer['contactPoint'] as $key => $value){
                if (empty($value)){
                    unset($post['Plan']['buyers'][$k]['contactPoint'][$key]);
                }
            }
        }

        if (empty( $post['Plan']['buyers'][0]['contactPoint']['name'])){
            unset($post['Plan']['buyers'][0]['contactPoint']);
        }

        if (!(isset($post['Plan']['procuringEntity']['is_changed']) && $post['Plan']['procuringEntity']['is_changed'] == 1)) {
            $post['Plan']['procuringEntity'] = ApiHelper::fillCompany();;
        }

        if (isset($post['Czo']) && $post['Czo'] != '') {
            $post['Plan']['procuringEntity'] = ApiHelper::fillCzoCompany($post['Czo']);
        }

        foreach ($post['Plan']['items'] as $i => $item) {
            if ($i === 'iClass' || $i === '__EMPTY_ITEM__') continue;
            if ($item['description'] != '') {
                $post['Plan']['items'][$i]['unit']['name'] = Unit::find()->where(['id' => $post['Plan']['items'][$i]['unit']['code']])->limit(1)->one()['name'];
            }
        }
        //ApiHelper::fillCompany($post['Plan']['procuringEntity']);

        $plan->load($post, 'Plan');
        $plan = HPlan::load($post);
        if ($validate) {

            // костыль для items
            $newItem = [];
            foreach ($plan->items as $i => $item) {
                if ($i === 'iClass' || $i === '__EMPTY_ITEM__') continue;
                if ($item->description != '') {
                    $newItem[] = $item;
                }
            }
            if (!count($newItem)) {
                $plan->items = [];
            } else {
                $plan->items = $newItem;
            }
            //-----------------------------------------------
            $plan->validate();
            $plan->cancellation = null;
//var_dump($plan);die;
//            \Yii::$app->VarDumper->dump($plan->validate(), 10, true);
//            \Yii::$app->VarDumper->dump($plan->getErrors(), 10, true, true);

            if (!$plan->validate()) {
                Yii::$app->session->setFlash('danger', 'Ошибка валидации');
                return false;
            }
        }

//        $plans->title       = $post['Plan']['budget']['project']['name'];
        $plans->description = $post['Plan']['budget']['description'];

        // костыль для items для post
        $newItem = [];
        foreach ($post['Plan']['items'] as $i => $item) {
//            if ($item['description'] != '') {
                $newItem[] = $item;
//            }
        }
        if (!count($newItem)) {
            $post['Plan']['items'] = [];
        } else {
            $post['Plan']['items'] = $newItem;
        }
        //-----------------------------------------------

        // костыль для breakdown для post
        $newItem = [];
        foreach ($post['Plan']['budget']['breakdown'] as $i => $breakdown) {
            if ($breakdown['title'] != '') {
                $newItem[] = $breakdown;
            }

        }
        if (!count($newItem)) {
            $post['Plan']['budget']['breakdown'] = [];
        } else {
            $post['Plan']['budget']['breakdown'] = $newItem;
        }
        //-----------------------------------------------

        if (Yii::$app->user->identity->test_mode) {
            $post['Plan']['mode'] = 'test';
        }


        if (isset($post['documents'])) {
            unset($post['documents']['__empty_doc__']);
            $post['Plan']['documents'] = $post['documents'];
        }

        $plans->code_cpv = $post['Plan']['classification']['id'];
        $plans->json = json_encode(['Plan' => $post['Plan']]);
        if (!$plans->plan_cbd_id) {
            $plans->status = 'draft';
        }


        $plans->data = ['data' => $post['Plan']];

        if (!$plans->save($validate)) {
            throw new ErrorException('Не удалось сохранить данные в DB');
        }else{
            $post['id'] = $plans->id;
        }


        if (isset($post['drafts']) && Yii::$app->params['elastic']) {
            \app\models\elasticModels\Plans::fillModelFromPlan($plans);
        }
        return true;
    }

    /**
     * @param Plans $plans
     *
     * @throws \Exception
     * @return bool
     */
    public static function  submitToApi($plans)
    {
        $post = Yii::$app->request->post();

        if (empty($post['Plan']['rationale']['description'])){
            unset($post['Plan']['rationale']);
        }

        if ($post['procurementMethod'] === 'open_closeFrameworkAgreementUA') {
            unset($plans->data['data']['budget']['year']);
            if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $plans->data['data']['budget']['period']['startDate'], $matches)) {
                $plans->data['data']['budget']['period']['startDate'] = '01/01/' . $matches[3] . ' 00:00:00';
            } else {
                $plans->data['data']['budget']['period']['startDate'] = '01/01/' . $plans->data['data']['budget']['period']['startDate'] . ' 00:00:00';
            }
            if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $plans->data['data']['budget']['period']['endDate'], $matches)) {
                $plans->data['data']['budget']['period']['endDate'] = '31/12/' . $matches[3] . ' 23:59:59';
            } else {
                $plans->data['data']['budget']['period']['endDate'] = '31/12/' . $plans->data['data']['budget']['period']['endDate'] . ' 23:59:59';
            }
        } else {
            if ($post['Plan']['budget']['period']['startDate'] == '') {
                unset($plans->data['data']['budget']['period']);
            }
        }
//        else {
//            $plans->data['data']['budget']['period']['startDate'] = '01/01/'. $plans->data['data']['budget']['year'] .' 00:00:00';
//            $plans->data['data']['budget']['period']['endDate'] = '31/12/'. $plans->data['data']['budget']['year'] .' 23:59:59';
//        }

        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $plans->data['data']['tender']['tenderPeriod']['startDate'])) {
            $plans->data['data']['tender']['tenderPeriod']['startDate'] = $plans->data['data']['tender']['tenderPeriod']['startDate'] . ' 00:00';
        } elseif (preg_match('/^\d{2}\/\d{4}$/', $plans->data['data']['tender']['tenderPeriod']['startDate'])) {
            $plans->data['data']['tender']['tenderPeriod']['startDate'] = '01/' . $plans->data['data']['tender']['tenderPeriod']['startDate'] . ' 00:00';
        }

        ApiHelper::FormatDate($plans->data['data'], true);

        //формуємо вміст частини Buyers та procuringEntity в моделі Plan
        $post['Plan']['buyers'][0] = array_merge($post['Plan']['buyers'][0], ApiHelper::fillCompany());

        foreach ($post['Plan']['buyers'] as $k => $buyer){
            foreach ($buyer['contactPoint'] as $key => $value){
                if (empty($value)){
                    unset($post['Plan']['buyers'][$k]['contactPoint'][$key]);
                }
            }
        }

        if (empty( $post['Plan']['buyers'][0]['contactPoint']['name'])){
            unset($post['Plan']['buyers'][0]['contactPoint']);
        }


        if (!(isset($post['Plan']['procuringEntity']['is_changed']) && $post['Plan']['procuringEntity']['is_changed'] == 1)) {
            $post['Plan']['procuringEntity'] = ApiHelper::fillCompany();;
        }else {
            unset($plans->data['data']['procuringEntity']['is_changed']);
        }

        if (isset($post['Czo']) && $post['Czo'] != '') {
            $plans->data['data']['procuringEntity'] = ApiHelper::fillCzoCompany($post['Czo']);
        }
//        \Yii::$app->VarDumper->dump($plans->data, 10, true, true);

//        Yii::$app->VarDumper->dump($plans->data['data'], 10, true, true);
//        if(isset($post['w']))

//        Yii::$app->VarDumper->dump($plans->data['data'], 10, true, true);
        if ($plans->data['data']['id'] == '') {
            unset($plans->data['data']['id']);
        }
        $plans->data['data']['budget']['id'] = md5($plans->data['data']['classification']['id']);

        $cpv_id = $plans->data['data']['classification']['id'];
//        if (!($cpv_id == '99999999-9' || strpos($cpv_id, '336') !== false)) {
//            unset($plans->data['data']['additionalClassifications'][0]);
//            foreach ($plans->data['data']['items'] as $k => &$item) {
//                unset($item['additionalClassifications']);
//            }
//        }
//        else if (strpos($cpv_id, '336') !== false) {
//            unset($plans->data['data']['additionalClassifications'][0]);
//        }

        foreach ($plans->data['data']['additionalClassifications'] as $k => &$item) {
            if (!$plans->data['data']['additionalClassifications'][$k]['id'] && $plans->data['data']['additionalClassifications'][$k]['scheme'] != '000') {
                unset($plans->data['data']['additionalClassifications'][$k]);
            }
        }

        //если ДК не выбран
        if ($cpv_id == '99999999-9') {
            if ($plans->data['data']['additionalClassifications'][0]['scheme'] == '000') {
                $plans->data['data']['additionalClassifications'][0]['scheme'] = 'specialNorms';
                $plans->data['data']['additionalClassifications'][0]['id'] = '000';
                $plans->data['data']['additionalClassifications'][0]['description'] = Yii::t('app', 'special_norms');
            }
            foreach ($plans->data['data']['items'] as $k => &$item) {
                if ($item['additionalClassifications'][0]['scheme'] == '000') {
                    $item['additionalClassifications'][0]['description'] = Yii::t('app', 'special_norms');
                    $item['additionalClassifications'][0]['scheme'] = 'specialNorms';
                    $item['additionalClassifications'][0]['id'] = '000';
                } // новый кусок
                elseif ($item['additionalClassifications'][0]['scheme'] == '') {
                    unset($item['additionalClassifications']);
                }
                /////////
            }
            unset($item);

        } else if (strpos($cpv_id, '336') !== false) {
            $plans->data['data']['additionalClassifications'] = self::fillInnRoot($plans->data['data']['additionalClassifications']);
            foreach ($plans->data['data']['items'] as $k => $item) {
                $plans->data['data']['items'][$k]['additionalClassifications'] = self::fillInn($plans->data['data']['items'][$k]['additionalClassifications']);
                if (!$item['additionalClassifications']) return false;
            }

        } else if ($plans->plan_id) {
            // если редактируем опубликованный
            $old_data = json_decode($plans->response, 1);
            if (isset($old_data['data']['additionalClassifications'][0]['scheme']) && in_array($old_data['data']['additionalClassifications'][0]['scheme'], ['ДК003', 'ДК015', 'ДК018'])) {
                if (!$plans->data['data']['additionalClassifications'][0]['dkType']) {
                    $plans->data['data']['additionalClassifications'][0] = ['scheme' => 'NONE', 'id' => '000', 'description' => ''];
                    foreach ($plans->data['data']['items'] as $k => &$item) {
                        //if (isset($item['additionalClassifications'][0]['scheme']) && in_array($item['additionalClassifications'][0]['scheme'],['ДК003','ДК015','ДК018'])) {
                        $item['additionalClassifications'][0] = ['scheme' => 'NONE', 'id' => '000', 'description' => ''];
                        //}
                    }
                }
            } else if (isset($old_data['data']['additionalClassifications'][0]['scheme']) && in_array($old_data['data']['additionalClassifications'][0]['scheme'], ['specialNorms'])) {
                $plans->data['data']['additionalClassifications'][0] = ['scheme' => 'NONE', 'id' => '000', 'description' => ''];
            }
        } /////////////////////
        else {
            foreach ($plans->data['data']['items'] as $k => &$item) {
                if ($item['additionalClassifications'][0]['scheme'] == '') {
                    unset($item['additionalClassifications']);
                }
                /////////
            }
            unset($item);
        }

        //////////////////////////
//        \Yii::$app->VarDumper->dump($plans->data['data'], 10, true, true);
        $plans->data['data']['additionalClassifications'] = array_values($plans->data['data']['additionalClassifications']);

        foreach ($plans->data['data']['items'] as $k => &$item) {
            unset($item['additionalClassifications'][0]['dkType']);
        }
        unset($item, $plans->data['data']['additionalClassifications'][0]['dkType']);

        if (!count($plans->data['data']['additionalClassifications'])) {
            $plans->data['data']['additionalClassifications'] = []; // ??
        }

        if (Yii::$app->user->identity->test_mode) {
            $plans->data['data']['mode'] = 'test';
        }

        // если к плану опубликован тендер, то нельзя редактировать procuringEntity
//        \Yii::$app->VarDumper->dump($plans->data['data'], 10, true, true);
        if (!empty($plans->tender)) {
            unset($plans->data['data']['procuringEntity']);
        }

        if (isset($plans->data['data']['documents'])) {
            unset($plans->data['data']['documents']);
        }


//        if ($plans->token) {
//            if ($plans->status == 'draft') {
//
//                $response = Api::get()->request([
//                    'point' => 'plans',
//                    'url' => $plans->plan_id . '?acc_token=' . $plans->token,
//                    'method' => 'PATCH',
//                    'data' => json_encode(['data' => ['status' => 'scheduled']])]);
//
//            }
//            //$response = Yii::$app->opAPI->tenders(json_encode($data), $tenders->tender_id, $tenders->token, null, $tenders->id);
//            $response = Api::get()->request([
//                'point' => 'plans',
//                'url' => $plans->plan_id . '?acc_token=' . $plans->token,
//                'method' => 'PATCH',
//                'data' => json_encode($plans->data)
//            ]);
//        } else {
//
//            /**
//             * Новый тендер отправляем черновиком
//             * А потом активируем, согласно Yii::$app->params['active_statuses']
//             */
//            $draft_token = null;
//            $plans->data['data']['status'] = 'draft';
//
//            for ($i = 0; $i <= Yii::$app->params['two_phase_commit_count']; $i++) {
//                try {
//                    if (!$draft_token) {
//
//                        $response = Api::get()->request([
//                            'point' => 'plans',
//                            'url' => $plans->plan_id ? $plans->plan_id . '?acc_token=' . $plans->token : '',
//                            'method' => $plans->plan_id ? 'PATCH' : 'POST',
//                            'data' => json_encode($plans->data),
//                        ]);
//
//
//                        if (isset($response['body']['access']) && $response['body']['access']) {
//                            $draft_token = $response['body']['access']['token'];
//                            $plans->plan_id = $response['body']['data']['id'];
//                            // токен миграции
//                            if (isset($response['body']['access']['transfer'])) {
//                                $plans->transfer_token = $response['body']['access']['transfer'];
//                            }
//                        }
//                    }
//                    if ($draft_token) {
//                        if (empty($plans->token)) {
//                            $plans->token = $draft_token;
//                        }
//                        //$response = Yii::$app->opAPI->tenders(json_encode(['data' => ['status' => Yii::$app->params['active_statuses'][$tenders->tender_method]]]), $tenders->tender_id, $draft_token);
//                        $response = Api::get()->request([
//                            'point' => 'plans',
//                            'url' => $plans->plan_id . '?acc_token=' . $draft_token,
//                            'method' => 'PATCH',
//                            'data' => json_encode(['data' => ['status' => 'scheduled']])]);
//                        break;
//                    }
//                } catch (apiDataException $e) {
//                    throw $e;
//                } catch (\Exception $e) {
//                    /** Игнорим ошибку 5 раз (Yii::$app->params['two_phase_commit_count']) */
//                    if (substr($e->getCode(), 0, 1) != '5') {
//                        throw $e;
//                    }
//                    if ($i == Yii::$app->params['two_phase_commit_count']) {
//                        //$tenders = $this->saveTender($post);
//                        //$tenders->saveTender($post);
////                        Yii::$app->session->setFlash('danger', Yii::t('app', 'error create tender on CBD'));
////                        return $tenders;
//                        Yii::$app->session->setFlash('danger', Yii::t('app', 'Ошибка публикации'));
//                        return false;
//                    }
//                }
//            }
//            /* ---------------------------------- */
//        }

//\Yii::$app->VarDumper->dump($plans->data, 10, true, true);
        try {
            if (!$plans->plan_id) {
                $plans->data['data']['status'] = 'draft';
            }

            $response = Api::get()->request([
                'point' => 'plans',
                'url' => $plans->plan_id ? $plans->plan_id . '?acc_token=' . $plans->token : '',
                'method' => $plans->plan_id ? 'PATCH' : 'POST',
                'data' => json_encode($plans->data),
            ]);
        } catch (\Exception $e) {
            if (method_exists($e,'getErrors')) {
                Yii::$app->session->setFlash('danger', Yii::t('app', 'CBD.return.data.error') . '<br />' . Yii::t('app', $e->getErrors()));
            } else {
                Yii::$app->session->setFlash('danger', Yii::t('app', 'CBD.return.data.error') . '<br />' . Yii::t('app', $e->getMessage()));
            }
            return false;
        }

//        //сохраняем предыдущую версию плана для сравнения и определения необходимости подписи ЕЦП
//        if($plans->response != ''){
//            $plans->prev_response = $plans->response;
//        }

        $plans->response = $response['raw'];
        $plans->status = $response['body']['data']['status'];
        $plans->date_modified = $response['body']['data']['dateModified'];
        $plans->code_cpv = $response['body']['data']['classification']['id'];

        // isNew
        if (isset($response['body']['access'])) {
            $plans->token = $response['body']['access']['token'];
            $plans->transfer_token = $response['body']['access']['transfer'];
        }
        $plans->plan_id = $response['body']['data']['id'];
        $plans->plan_cbd_id = $response['body']['data']['planID'];
        $plans->save(false);

        return $plans;
    }

    private static function fillInn($arr)
    {
        if (isset($arr[0]['dkType']) && $arr[0]['dkType'] == 'INN/APN_none') {
            return [];
        }

        $inn = Inn::findOne(['id' => $arr[0]['id']]);
        if (!$inn) {
            $inn = Inn::findOne(['scheme_id' => $arr[0]['id']]);
            if (!$inn) {
                return false;
            }
        }

        $arr[0]['scheme'] = $inn->scheme;
        $arr[0]['id'] = $inn->scheme_id;
        if ($inn->scheme == 'ATC') {
            $arr[0]['description'] = explode(' ', $inn->name, 2)[1];
            $inn2 = Inn::findOne(['id' => $inn->pid]);
            $arr[1]['scheme'] = $inn2->scheme;
            $arr[1]['id'] = $inn2->scheme_id;
            $arr[1]['description'] = $inn2->name;
        } else {
            $arr[0]['description'] = $inn->name;
        }
        unset($arr[0]['dkType'], $arr[1]['dkType']);
        return $arr;
    }

    private static function fillInnRoot($arr)
    {
        if (count($arr) > 1) {
            $inn = self::fillInn([$arr[0]]);
            unset($arr[0]);
            return array_merge($inn,$arr);
        } else {
            return self::fillInn($arr);
        }
    }


    /**
     * Возвращает возможные типы тендеров по типу компании
     *
     * @return array
     */
    public function getPlanProcurementMethod()
    {
//        if (in_array($this->tender_method, ['selective_closeFrameworkAgreementSelectionUA'])) {
//            return [
//                'selective_closeFrameworkAgreementSelectionUA' => Yii::t('app', 'selective_closeFrameworkAgreementSelectionUA'),
//            ];
//        }
//        if (in_array($this->tender_method, ['selective_competitiveDialogueUA.stage2', 'selective_competitiveDialogueEU.stage2'])) {
//            $ext = [
//                'selective_competitiveDialogueUA.stage2' => Yii::t('app', 'Конкурентний діалог 2 етап'),
//                'selective_competitiveDialogueEU.stage2' => Yii::t('app', 'Конкурентний діалог з публікацією англ. мовою 2 етап'),
//            ];
//        } else {
//
//        }
        $ext = [];
        $types = [
            //'' => \Yii::t('app', 'Без застосування електронної системи'),
            'open_belowThreshold' => Yii::t('app', 'Спрощена закупівля'),
            'limited_reporting' => Yii::t('app', 'Звіт про укладений договір'),
            'limited_negotiation' => Yii::t('app', 'Переговорна процедура'),
            'limited_negotiation.quick' => Yii::t('app', 'Переговорна процедура, скорочена'),
            //'open_aboveThresholdUA.defense' => Yii::t('app', 'Закупiвлi в особливий перiод'),
            'open_aboveThresholdUA' => Yii::t('app', 'Українська процедура вiдкритих торгiв'),
            'open_aboveThreshold' => Yii::t('app', 'Відкриті торги з особливостями'),
            'open_aboveThresholdEU' => Yii::t('app', 'Європейська процедура вiдкритих торгiв'),
            'open_competitiveDialogueUA' => Yii::t('app', 'Конкурентний діалог'),
            'open_competitiveDialogueEU' => Yii::t('app', 'Конкурентний діалог з публікацією англ. мовою'),
            'open_closeFrameworkAgreementUA' => Yii::t('app', 'Укладання рамкової угоди'),
            'open_esco' => Yii::t('app', 'open_esco'),
            '_centralizedProcurement' => Yii::t('app', 'Закупiвля через централізовану закупівельну організацію'),
            'selective_priceQuotation' => 'Запит ціни пропозиції',
            'selective_competitiveOrdering' => 'Тендер'
        ];

        switch (Yii::$app->user->identity->company->customer_type) {

            case 'defense':
                $ext = array_merge($ext, [
                    //'open_aboveThresholdUA.defense' => Yii::t('app', 'Закупiвлi в особливий перiод'),
                    //'open_simple.defense' => Yii::t('app', 'Спрощені торги із застосуванням електронної системи закупівель для потреб оборони'),
                ]);
                return array_merge($types, $ext);
                break;

            case 'other':
                return [
                    'open_belowThreshold' => Yii::t('app', 'Спрощена закупівля'),
                    'limited_reporting' => Yii::t('app', 'Звіт про укладений договір'),
                ];
                break;
        }

        return $types;
    }


    public static function getPlanProcurementMethodView()
    {
        return [
            '' => \Yii::t('app', 'Без застосування електронної системи'),
            'open_belowThreshold' => Yii::t('app', 'Звичайна процедура'),
            'limited_reporting' => Yii::t('app', 'Звіт про укладений договір'),
            'limited_negotiation' => Yii::t('app', 'Переговорна процедура'),
            'limited_negotiation.quick' => Yii::t('app', 'Переговорна процедура, скорочена'),
            'open_aboveThresholdUA.defense' => Yii::t('app', 'Закупiвлi в особливий перiод'),
            'open_simple.defense' => Yii::t('app', 'Спрощені торги із застосуванням електронної системи закупівель для потреб оборони'),
            'open_aboveThresholdUA' => Yii::t('app', 'Українська процедура вiдкритих торгiв'),
            'open_aboveThreshold' => Yii::t('app', 'Відкриті торги з особливостями'),
            'open_aboveThresholdEU' => Yii::t('app', 'Європейська процедура вiдкритих торгiв'),
            'open_competitiveDialogueUA' => Yii::t('app', 'Конкурентний діалог'),
            'open_competitiveDialogueEU' => Yii::t('app', 'Конкурентний діалог з публікацією англ. мовою'),
            'open_closeFrameworkAgreementUA' => Yii::t('app', 'Укладання рамкової угоди'),
            'open_esco' => Yii::t('app', 'open_esco'),
            '_centralizedProcurement' => Yii::t('app', 'Закупiвля через централізовану закупівельну організацію'),
            'selective_priceQuotation' => 'Запит ціни пропозиції',
        ];
    }

    /**
     * Проверят владелец ли текущий компания текущего аукциона
     *
     * Если указан $cID - то по companyID
     *
     * @param integer|null $cID
     * @return bool
     */
    public function isOwner($cID = null)
    {
        if (Yii::$app->user->isGuest || Yii::$app->user->identity->company->is_seller) {
            return false;
        }

        if ($this->plan_cbd_id) {
            if (!$this->token) {
                return false;
            }

            if (!$this->transfer_token) {
                return false;
            }
        }

        if ($cID == null) {
            return $this->company_id == Yii::$app->user->identity->company->id || $this->user_id == Yii::$app->user->identity->id;
        } else {
            return $this->company_id == $cID;
        }
    }

    public function beforeDelete()
    {
        if (!$this->isOwner()) {
            Yii::$app->session->setFlash('danger', Yii::t('app', 'false.plan.owner'));

            return false;
        }

        if ($this->status != 'draft') {
            Yii::$app->session->setFlash('danger', Yii::t('app', 'plan.is.published'));

            return false;
        }

        return parent::beforeDelete();
    }

    public function afterDelete()
    {
        Yii::$app->session->setFlash('success', Yii::t('app', 'plan.deleted'));

        parent::afterDelete();
    }

    /**
     * Обновление плана из ЦБД
     * @param $id
     */
    public static function apiUpdate($id)
    {
        $model = new PlanUpdateTask();
        $model->pid = $id;
        $model->updatePlanApi();
    }

    /**
     * Возвращает стиль панели тендеров на странице просмотра тендеров
     *
     * @return string
     */
    public function getPanelStyle()
    {
        $classes = ' ';
        switch ($this->status) {
            case 'active.tendering':
                $classes .= 'valid-bg';
                break;
            case 'cancelled':
                $classes .= 'invalid-bg';
                break;
            case 'unsuccessful':
                $classes .= 'invalid-bg';
                break;
            default:
                return '';
        }
        return $classes;
    }

    public function complete($plans)
    {
        try {
            $response = Api::get()->request([
                'point' => 'plans',
                'url' => $plans->plan_id . '?acc_token=' . $plans->token,
                'method' => 'PATCH',
                'data' => json_encode(['data' => ['status' => 'complete']])
            ]);

        } catch (\Exception $e) {
            Yii::$app->session->setFlash('danger', Yii::t('app', 'CBD.return.data.error') . '<br />' . $e->getErrors());
            return false;
        } catch (apiDataException $e) {
            Yii::$app->session->setFlash('danger', Yii::t('app', 'CBD.return.data.error') . '<br />' . $e->getErrors());
            return false;
        }
        return true;
    }

    public static function cleanUnsynchronized()
    {
        $syncAmount = Params::getValueByParam(Params::_SYNC_PLAN_AMOUNT);
        $syncDelay = Params::getValueByParam(Params::_SYNC_PLAN_DELAY);
        $isSync = $syncDelay>0?true:false;
        $syncAmountCounter = 0;

        $lastUnsynchronizedFile = dirname(__DIR__) . '/unsynchronized-plans.txt';
        if (file_exists($lastUnsynchronizedFile)) {
            $stat = stat($lastUnsynchronizedFile);
            if ($stat[9] > time() - 4 * 60) {
                // файл с id обновляли менее 4 мин назад
                return;
            }
            $lastUnsynchronizedId = intval(file_get_contents($lastUnsynchronizedFile));
        } else {
            $lastUnsynchronizedId = 0;
        }

        for ($nextId = self::getNextId($lastUnsynchronizedId); $nextId !== false; $nextId = self::getNextId($lastUnsynchronizedId)) {
            $plan = self::isPlanExists($nextId);
            if ($plan !== true) {
                self::removePlan($plan);
            }
            file_put_contents($lastUnsynchronizedFile, $nextId);
            $lastUnsynchronizedId = $nextId;

            if($isSync) {
                $syncAmountCounter++;
                if($syncAmountCounter>=$syncAmount) {
                    sleep($syncDelay);
                    $syncAmountCounter = 0;
                }
            }
        }
        unlink($lastUnsynchronizedFile);
    }

    private static function getNextId($lastUnsynchronizedId)
    {
        $query = new Query();
        $id = $query->select('id')
            ->from(self::tableName())
            ->where(['>', 'id', $lastUnsynchronizedId])
            ->andWhere(['is_cbd_deleted' => 0])
            ->orderBy('id')
            ->limit(1)
            ->one();
        $id = intval($id['id']);
        return $id;
    }

    private static function isPlanExists($id)
    {
        $plan = self::findOne($id);
        $url = Yii::$app->params['Api']['service']['api']['url'] . 'plans/' . $plan->plan_id;
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $out = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        if ($code == 200) {
            $arr = json_decode($out, true);
            if (is_array($arr)) {
                if ($arr['status'] != 'error') {
                    return true;
                }
            }
        }
        return $plan;
    }

    private static function removePlan($plan)
    {
        $elastic = \app\models\elasticModels\Plans::findOne($plan->plan_id);
        $elastic->delete();
        $plan->is_cbd_deleted = 1;
        $plan->save(false);
    }
}
