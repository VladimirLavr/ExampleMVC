<?php

use app\models\Companies;
use yii\helpers\Html;
use yii\widgets\ListView;

/* @var $this app\components\View */
/* @var $searchModel app\models\PlansSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
if ((\Yii::$app->controller->module->id == 'buyer') && (Yii::$app->controller->action->id == 'created-for-me')) {
    $title = Yii::t('app', 'Plan for me');
} else {
    $title = Yii::t('app', 'Plan');
}

$this->params['breadcrumbs'][] = $this->title($title, ['{posttitle}' => $title]);
$this->title($this->title);
$this->description($this->title);
?>
<div class="container">
    <h1><?= $this->h1($title, ['{posttitle}' => $title]) ?></h1>
    <div class="page-panel">
        <div class="search-form">
            <div class="new-object">
                <? if (Companies::checkCompanyIsBuyer()) {
                    if (Yii::$app->user->identity->company->isCanWork()) {
                        echo Html::a(Yii::t('app', 'Створити рядок плану закупівлі'), ['/buyer/plan/create'], ['class' => 'mk-btn mk-btn_accept pull-right']);
                    } else {
                        $this->registerJs("$(document).ready(function(){ $('[data-toggle=\"popover\"]').popover(); });", yii\web\View::POS_END);
                        $options['data-toggle'] = 'popover';
                        $options['data-trigger'] = 'hover';
                        $options['data-placement'] = 'bottom';
                        $options['data-content'] = Yii::t('app', 'company.not.identified');
                        $options['class'] = 'mk-btn mk-btn_accept mk-disabled pull-right';
                        echo Html::button(Yii::t('app', 'Create Plan'), $options);
                    }
                } ?>
            </div>
            <div class="clearfix"></div>
            <?php echo $this->render('_search', ['searchModel' => $searchModel]); ?>
        </div>
    </div>
</div>
<div class="container">
    <div class="page-panel">
        <div class="search-content">
            <?= ListView::widget([
                'dataProvider' => $dataProvider,
                'summaryOptions' => [
                    'class' => 'summary',
                ],
                'itemOptions' => [
                    'class' => 'search-result',
                ],
                'layout' => '{summary}{items}<div class="text-center">{pager}</div>',
                'itemView' => function ($model, $key, $index, $widget) {
                    return $this->render('_item_list', [
                        'model' => $model,
                        'key' => $key,
                    ]);
                },
            ]); ?>
        </div>
    </div>
</div>

<?php
echo $this->render('@app/views/tender/classificator_modal');
//$this->registerJsFile(Url::to('@web/js/project.js'), ['position' => yii\web\View::POS_END, 'depends' => 'yii\web\JqueryAsset']);
?>
