<?php
use yii\helpers\Html;

/**
 * @var \app\models\Plans $model
 * @var integer $key
 */
?>
<div class="row text-center_xs" <?= (\yii\helpers\Json::decode($model->response)['data']['mode'] === 'test' ? 'style="opacity: 0.7;"' : '')  ?>>
    <div class="col-xs-12 col-sm-8 col-md-9">
        <div class="search-result_article">
            <?= Html::encode($model->plan_cbd_id) ?>
        </div>
        <div class="search-result_t">
            <? if ($model->date_modified) { ?>
                <?= Html::encode(Yii::$app->formatter->asDatetime($model->date_modified)) ?>
            <? } else { ?>
                <?= Html::encode(Yii::$app->formatter->asDatetime($model->created_at)) ?>
            <? } ?>
        </div>
        <div class="search-result_d">
            <?= Html::encode($model->description) ?>
        </div>
        <? if (\yii\helpers\Json::decode($model->response)['data']['mode'] === 'test') { ?>
        <div>
            <small><i style="color: #721c24"><?= Yii::t('app', 'план в тестовому режимі')?></i></small>
        </div>
        <? } ?>
    </div>
    <div class="col-xs-12 col-sm-4 col-md-3 text-center">
        <?php if (\yii\helpers\Json::decode($model->response)['data']['budget']['amount'] == '0') { ?>
            <div>
                <span class="search-result_status-term invalid-bg">
                    <?= Yii::t('app', 'Canceled') ?>
                </span>
            </div>
            <? if ($model->isOwner()) {
                echo Html::a(Yii::t('app', 'detail'), Yii::$app->urlManager->createAbsoluteUrl(['/' . Yii::$app->session->get('businesType') . '/plan/view', 'id' => $key]), ['class' => 'mk-btn mk-btn_default']);
            }
        } else {
            echo Html::a(Yii::t('app', 'detail'), Yii::$app->urlManager->createAbsoluteUrl(['/' . Yii::$app->session->get('businesType') . '/plan/view', 'id' => $key]), ['class' => 'mk-btn mk-btn_default']);
        } ?>
        <div class="auction-item_status-term">
            <?= Yii::t('app', "plan_status_{$model->status}") ?>
        </div>
    </div>
</div>
