<?php

namespace app\modules\buyer\controllers;

use app\components\Api;
use app\components\apiDataException;
use app\components\apiException;
use app\components\HPlan;
use app\components\SimpleTenderConvertIn;
use app\models\Companies;
use app\models\DocumentUploadTask;
use app\models\LoginForm;
use app\models\Params;
use app\models\planModels\Milestones;
use app\models\planModels\Rationale;
use app\models\Plans;
use app\models\PlansSearch;
use app\models\tenderModels\Document;
use yii\helpers\ArrayHelper;
use function Couchbase\defaultDecoder;
use yii\base\ErrorException;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\Controller;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use app\models\planModels\Plan;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Class TenderController
 * @package app\controllers
 */
class PlanController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'denyCallback' => function ($rule, $action) {
                    Yii::$app->session->setFlash('warning', Yii::t('app', 'no.access'));
                    $this->redirect(Url::toRoute('/pages/company-auth'));
                },

                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            return true;
//                            if (Yii::$app->request->get('id')) {
//                                $res = Plans::findOne(Yii::$app->request->get('id'))->company_id;
//                                return Yii::$app->user->identity->company_id == $res && Companies::checkAllowedCompanyStatusToWork(Yii::$app->user->identity->company_id);
//                            } else {
//                                return Companies::checkAllowedCompanyStatusToWork(Yii::$app->user->identity->company_id);
//                            }

                        },
                    ],
                ],

            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }


    /**
     * Lists all Invite models.
     * @return mixed
     */
    public function actionIndex()
    {
        if (Params::getValueByParam(Params::_ELASTIC_SEARCH_FORM)) {
            $searchModel = new \app\models\elasticModels\PlansSearch();

            return $this->render('elastic/searchForm', [
                'model' => $searchModel,
            ]);
        } else {
            $searchModel = new PlansSearch();
            $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

            return $this->render('index', [
                'searchModel' => $searchModel,
                'dataProvider' => $dataProvider,
            ]);
        }
    }


    public function actionCreate()
    {

        if (Yii::$app->user->identity->company->status != 1) {
            throw new ForbiddenHttpException(Yii::t('app', 'Необхідно фінансово авторизуватись'));
        }

        $post = Yii::$app->request->post();

        if (isset($post['cancel'])) {
            return $this->redirect('index');
        }

        if (isset($post['drafts']) or isset($post['autosave']) or isset($post['publish'])) { //если нажата кнопка "Опубликовать"б "сохранить в черновик" или (автосейв)
            if (isset($post['id']) && $post['id']) {
                $plans = Plans::getModel($post['id']);
            } else {
                $plans = new Plans();
            }

            $plan = HPlan::update($post['Plan']);

            if ($rez = $this->edit($plans, $post, $plan)) {
                return $rez;
            }

        } else {
            $plan = HPlan::create();
            //$plan = new Plan([], [], 'create');
            $plans = new Plans();
        }

        return $this->render('create', [
            'id' => '',
            'published' => 0,
            'persons' => Yii::$app->user->identity->company->persons,
            'plan' => $plan,
            'plans' => $plans
        ]);

    }

    public function actionHistory($id){

        $plans = self::findModel($id);

        if(!empty($plans->plan_cbd_id)) {
            $response = Api::get()->request([
                'point' => 'history/plans',
                'service' => Yii::$app->params['usePublicPoint'] ? 'pub' : 'api',
                'url' => $plans->plan_id,
            ]);

            if (empty($response['body']['data']['changes'])) {
                Yii::$app->session->setFlash('info', Yii::t('app', 'Зміни не вносилися'));
                //return $this->redirect(['view', 'id' => $id]);
            }

            return $this->render('history', [
                'changes' => $response['body']['data']['changes'],
                'plans' => $plans
            ]);
        }else{
            Yii::$app->session->setFlash('info', Yii::t('app', 'Зміни не вносилися'));
        }
        return $this->redirect(['view', 'id' => $id]);
    }

    public function actionRationale($id)
    {
        $plans = self::findModel($id);

        if ($plans->isOwner()){
        $rationale = new Rationale();
        $post = Yii::$app->request->post();

        if ($post['description']) {
            if ($rationale->validate('description', $post['description'])) {

                try {
                   $response = Api::get()->request([
                        'point' => 'plans',
                        'url' => $plans->plan_id . '?acc_token=' . $plans->token,
                        'method' => 'PATCH',
                        'data' => json_encode(['data' => ['rationale' => ['description' => $post['description']]]]),
                    ]);

                    Plans::apiUpdate($plans->id);

                } catch (apiDataException $e) {
                    Yii::$app->session->setFlash('danger', Yii::t('app', 'CBD.return.data.error') . '<br />' . $e->getErrors());
                }
                if ($response){
                    return $this->redirect(['/buyer/plan/history', 'id' => $id]);
                }
            }
        }
            return $this->render('_add_rationale', [
                'rationale' => $rationale,
                'plans' => $plans
            ]);
            }

        return $this->redirect(['view', 'id' => $id]);

    }


    public function actionUpdate($id)
    {
        if (Yii::$app->user->identity->company->status != 1) {
            throw new ForbiddenHttpException(Yii::t('app', 'Необхідно фінансово авторизуватись'));
        }

        $plans = Plans::getModel($id);
        if (!$plans->isOwner()) {
            Yii::$app->session->setFlash('danger', Yii::t('app', 'no.access'));
            return $this->redirect(['view', 'id' => $id]);
        }

        $post = Yii::$app->request->post();

        if ($post) {

            $plan = HPlan::load();

            if (isset($post['cancel'])) {
                return $this->redirect(['view', 'id' => $id]);
            }
            if (isset($post['drafts']) or isset($post['autosave']) or isset($post['publish'])) { //если нажата кнопка "сохранить в черновик" или автосейв

                if ($rez = $this->edit($plans, $post, $plan)) {
                    return $rez;
                } else {
                    return $this->redirect(['plan/update/' . $plans->id]);
                }
            }

        } else {
            // да,я в курсе))
            if ($plans->response) {
                $data = ['Plan' => json_decode($plans->response, 1)['data']];
                unset($data['Plan']['id']);
                $data = HPlan::fixIsChanged($data, $plans);
            }
//            elseif (!$plans->json) {
//                $data = ['Plan' => json_decode($plans->response, 1)['data']];
//            }
            else {
                $data = json_decode($plans->json, 1);
            }
//                \Yii::$app->VarDumper->dump($data, 10, true, true);
            Document::setVersionOfAllDocuments($data);
            SimpleTenderConvertIn::FormatDate($data, 'startDate');
            SimpleTenderConvertIn::FormatDate($data, 'endDate');
            SimpleTenderConvertIn::FormatDate($data, 'datePublished');
            $plan = HPlan::update($data);

        }

        return $this->render('create', [
            'id' => $id,
            'published' => !empty($plans->plan_id),
            'persons' => Yii::$app->user->identity->company->persons,
            'plan' => $plan,
            'plans' => $plans
        ]);
    }

    public function actionComplete($id)
    {
        $plans = Plans::getModel($id);

        if (!$plans->isOwner()) {
            Yii::$app->session->setFlash('danger', Yii::t('app', 'no.access'));
            return $this->redirect(['view', 'id' => $id]);
        }

        $post = Yii::$app->request->post();

        if ($post) {
            //$plan = HPlan::load();
            if ($plans->complete($plans)) {
                //Plans::apiUpdate($plans->id);
                Yii::$app->session->setFlash('info', Yii::t('app', 'Статус оновиться протягом 5 хвилин'));
                return $this->redirect(['view', 'id' => $id]);
            }
            return $this->redirect(['view', 'id' => $id]);
        }
    }

    public function actionDelete($id)
    {
        $plan = $this->findModel($id);
        if ($plan->delete()) {
            if (Yii::$app->params['elastic']) {
                $res = \app\models\elasticModels\Plans::findOne($id);
                $res->delete();
            }
            return $this->redirect(['index']);
        } else {
            throw new ForbiddenHttpException(Yii::t('app', 'Forbidden'), 403);
        }
    }

    public function actionView($id)
    {
        try {
            Plans::apiUpdate($id);
        } catch (\Exception $e) {
            sleep(5);
            Plans::apiUpdate($id);
        }

        $plan = new Plan([], [], 'view');
        $plans = Plans::getPlans($id);
        $post = Yii::$app->request->post();

        if (isset($post['LoginForm']['password']) && $plans->isOwner()) {

            $post['LoginForm']['username'] = Yii::$app->user->identity->username;
            $login = new LoginForm();
            $login->load($post);

            if ($login->validate()) {
                Yii::$app->session->setFlash('message', Yii::t('app', 'get_transfer_alert', ['token' => $plans->transfer_token]));
                Yii::$app->sLog->event('Удачное получение transfer token', $plans->plan_cbd_id);
            } else {
                Yii::$app->session->setFlash('message', Yii::t('app', 'Не вiрний пароль'));
                Yii::$app->sLog->event('Не удачное получение transfer token', $plans->plan_cbd_id);
            }

            return $this->redirect(Url::current());

        }

        if (isset($post['activate_without_eds']) && $plans->isOwner()) {
            try {
                Api::get()->request([
                    'point' => 'plans',
                    'url' => $plans->plan_id . '?acc_token=' . $plans->token,
                    'method' => 'PATCH',
                    'data' => json_encode(['data'=>['status'=>'scheduled']]),
                ]);

                Plans::apiUpdate($plans->id);

            } catch (apiDataException $e) {
                Yii::$app->session->setFlash('danger', Yii::t('app', 'CBD.return.data.error') . '<br />' . $e->getErrors());
            }
            return $this->redirect(['/buyer/plan/view', 'id' => $id]);
        }

        if ($plans->status == 'draft' && !$plans->isOwner()) {
            throw new ForbiddenHttpException(Yii::t('app', 'Forbidden'), 403);
        }

        $plan->load($plans->json, 'Plan');

        return $this->render('view', [
            'plan' => $plan,
            'plans' => $plans
        ]);
    }


    /**
     * @param $plans Plans
     * @param $post  array
     * @param $plan  Plan
     *
     * @return string|\yii\web\Response
     * @throws ErrorException
     */
    public function edit(&$plans, &$post, &$plan)
    {
        if (Plans::submitToDB($plans, $plan,$post, isset($post['publish']))) {
            if (isset($post['publish'])) {
                if (!Plans::submitToApi($plans)) {
                    Yii::$app->session->setFlash('warning', Yii::t('app', 'Ошибка публикации'));
                    return false;
                }
                Yii::$app->getSession()->setFlash('warning', Yii::t('app', 'plan.ecp.required'));
                $plans->ecp = 0;
                $plans->save();

                if ($post['documents']) {
                    $docUrl = $plans->plan_id;
                    DocumentUploadTask::updateTableAfterSavePlan($plans->id, $docUrl, $plans->token, $post, $plans->response);
                    Yii::$app->session->setFlash('info', Yii::t('app', 'Додані файли будуть завантажені протягом 5 хвилин.'));
                }
            }
            if (Yii::$app->request->isAjax) {
                $res = [
                    'key' => Yii::$app->request->csrfToken,
                    'plan_id' => $plans->id];
                return json_encode($res);
            } else {
                if (isset($post['publish'])) {
                    //Plans::apiUpdate($plans->id);
                    Yii::$app->session->setFlash('success', \Yii::t('app', 'plan_sended'));
                } else {
                    Yii::$app->session->setFlash('success', \Yii::t('app', 'Saved to draft'));
                }
                //Plans::apiUpdate($plans->id);
                return $this->redirect(['plan/view/' . $plans->id]);
            }
        }
        return false;
    }

    public function actionCancel($id)
    {
        $plan = new Plan([], [], 'view');
        $plans = Plans::getPlans($id);
        $post = Yii::$app->request->post();

        if ($post) {

            Yii::$app->sLog->event('Отмена плана', $plans->plan_cbd_id);

            $cancellation = $post['Plan'];
            $cancellation['cancellation']['status'] = 'active';

            try {
                $cancel_response = Api::get()->request([
                    'point' => 'plans',
                    'url' => $plans->plan_id . '?acc_token=' . $plans->token,
                    'method' => 'PATCH',
                    'data' => json_encode(['data' => $cancellation]),
                ]);

                Yii::$app->session->setFlash('info', Yii::t('app', 'План скасовано. Статус оновиться протягом 5 хвилин'));
                Plans::apiUpdate($id);
                return $this->redirect(Url::toRoute('/buyer/plan/view/' . $id, true));

            } catch (apiDataException $e) {

                Yii::$app->session->setFlash('danger', Yii::t('app', 'CBD.return.data.error') . '<br />' . $e->getErrors());
                return false;
            }

        }

        Yii::$app->sLog->event('Просмотр отмены плана', $plans->plan_cbd_id);

        $plan->load($plans->json, 'Plan');

        return $this->render('cancel', [
            'plan' => $plan,
            'plans' => $plans
        ]);
    }

    public function publishCancel($plans, $post)
    {

        if (!$plans->isOwner()) return false;

        $cancel = new Cancellation();

        if (SimpleTenderConvertOut::prepareToValidateCancel($post, $cancel)) {
            if ($cancel_id = SimpleTenderConvertOut::sendCancellations($plans->id, $plans->plan_id, $plans->token, $post, $plans->response)) {

                return true;
            }
        }
        return false;
    }

    protected function findModel($id)
    {
        if (($model = Plans::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
        }
    }

}
