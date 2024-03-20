<?php

use yii\helpers\Html;
use \app\models\Companies;

/**
 * @var $plan      app\models\planModels\Plan
 * @var $plans     app\models\Plans
 * @var $published bool
 */
$published = !empty($plans->plan_cbd_id);
$fieldLabel = $plan->attributeLabels();
$title = $plan->id ? $plan->planID : $plans->id;
$this->title($title, ['{posttitle}' => substr($plans['description'], 0, 150), '{postid}' => $plan->planID]);
$this->description($title, ['{posttitle}' => substr($plans['description'], 0, 250), '{postid}' => $plan->planID]);
$this->registerJs('
var test_mode_reload = true;
', yii\web\View::POS_BEGIN);

?>
    <div class="container">
        <h1>
            <?= $this->title ?>
        </h1>
        <? if ($plan->mode === 'test') { ?>
            <div class="message message_danger text-center" style="padding: 4px;margin: 5px 0;">
                <small><i><?= Yii::t('app', 'план в тестовому режимі') ?></i></small>
            </div>
        <? } ?>
        <div class="page-panel">
            <?= $this->render('@app/views/plan/small_info_block', [
                'plan' => $plan,
                'plans' => $plans,
            ]); ?>

            <? if (Yii::$app->session->hasFlash('message')) { ?>
                <div class="bs-example">
                    <div class="message message_info">
                        <?= Yii::$app->session->getFlash('message'); ?>
                    </div>
                </div>
            <? } ?>

            <div class="row item-h">
                <? if ($plan->id) { ?>
                    <div class="item-inf">
                        <div class="col-xs-12 text-center_xs">
                            <div class="row">
                                <div class="col-xs-12 col-sm-6 col-md-4 item-bl_t">
                                    <?= Yii::t('app', 'PlanID') ?>
                                </div>
                                <div class="col-xs-12 col-sm-6 col-md-8 item-bl_val" data-test-id="planID">
                                    <?= $plan->planID ?>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-12 col-sm-6 col-md-4 item-bl_t">
                                    ID
                                </div>
                                <div class="col-xs-12 col-sm-6 col-md-8 item-bl_val" data-test-id="id">
                                    <?= $plan->id ?>
                                </div>

                            </div>
                            <?php if (!empty($plans->tender) && !$plans->tender[0]->hidden) { ?>
                                <div class="row">
                                    <div class="col-xs-12 col-sm-6 col-md-4 item-bl_t">
                                        <?= Yii::t('app', 'TenderID') ?>
                                    </div>
                                    <div class="col-xs-12 col-sm-6 col-md-8 item-bl_val" data-test-id="TenderID">
                                        <?= $plans->tender[0]->tender_cbd_id ?>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>


                    <div class="col-xs-12">
                        <?php if ($plan->canCreateTenderFromPlan($plans)) { //&& $plan->checkSign($plans)
                            echo $this->render('@app/views/plan/_create_modal_form', [
                                'plan' => $plan
                            ]);
                        } else if (!empty($plans->tender) && $plans->tender[0]->hidden) { ?>
                            <div class="message message_info">
                                <?php if (in_array($plans->tender[0]->tender_method, ['limited_negotiation', 'limited_negotiation.quick'])) { ?>
                                    <?= Yii::t('app', 'limited.attention.message.create.page') ?>
                                <?php } elseif (in_array($plans->tender[0]->tender_method, ['limited_reporting'])) { ?>
                                    <?= Yii::t('app', 'limited.reporting.attention.message.create.page') ?>
                                <?php } ?>
                            </div>
                        <?php } else if (!empty($plans->tender) && !$plans->tender[0]->hidden && $plans->tender[0]->test_mode === Yii::$app->user->identity->test_mode) {
                            echo Html::a(Yii::t('app', 'Перейти до закупiвлi'), ['/' . \app\models\Companies::getCompanyBusinesType() . '/tender/view', 'id' => $plans->tender[0]->tender_cbd_id], ['class' => 'mk-btn mk-btn_accept']);
                        } else if (in_array($plan->tender->procurementMethodType, ['aboveThresholdUA.defense', 'open_simple.defense']) && Yii::$app->user->identity->company->customer_type !== 'defense') { ?>
                            <div class="message message_danger">
                                <?= Yii::t('app', 'Увага! Зв`язка плану з тендером заблокована. Тип цієї закупівлі не співпадає з типом замовника.') ?>
                            </div>
                        <? }
                        if ($plan->tender->procurementMethodType === 'priceQuotation') { ?>
                            <div class="message message_info">
                                <?= Yii::t('app', 'Увага! Закупівлі створюються через Prozorro.Market.') ?>
                            </div>
                        <? }
                        if ($published && !Yii::$app->user->isGuest && (($plan->mode === 'test' && !Yii::$app->user->identity->test_mode) || (!$plan->mode && Yii::$app->user->identity->test_mode))) { ?>
                            <div class="message message_danger">
                                <?= Yii::t('app', 'Увага! Зв`язка плану з тендером заблокована. Тестовий режим плану та користувача не співпадають') ?>
                            </div>
                        <? } ?>
                    </div>

                <? } ?>

                <? if ($published) { ?>
                    <div class="col-xs-12">
                        <div class="message message_warning">
                            <?= $plan->checkSign($plans) ? Yii::t('app', 'tender.condition.sign') : Yii::t('app', 'tender.condition.no.sign') ?>
                        </div>
                    </div>
                <? } ?>
                <? if ($plan->status == 'draft' && $plan->id) { ?>
                    <div class="col-xs-12">
                        <div class="message message_warning">
                            <?= Yii::t('app', 'Рядок плану закупівлі буде активовано та переведено в статус "Заплановано" після накладання КЕП') ?>
                        </div>
                    </div>
                <? } ?>
                <div class="clearfix"></div>
                <?php
                //            \Yii::$app->VarDumper->dump($plan->cancellation, 10, true, true);
                ?>
                <?php if (!is_null($plan->cancellation->reason)) { ?>
                    <div class="col-xs-12">
                        <div class="message message_danger">
                            <div class="row">
                                <div class="col-xs-12 col-sm-6 col-md-4">
                                    <?= Yii::t('app', 'Причина скасування'); ?>
                                </div>
                                <div class="col-xs-12 col-sm-6 col-md-8 item-bl_val">
                                    <?= $plan->cancellation->reason ?>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-12 col-sm-6 col-md-4">
                                    <?= Yii::t('app', 'Дата скасування'); ?>
                                </div>
                                <div class="col-xs-12 col-sm-6 col-md-8 item-bl_val">
                                    <?= Yii::$app->formatter->asDatetime($plan->cancellation->date) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>


            </div>

            <?php if (isset($plan->buyers) && !empty($plan->buyers)) { ?>
                <?php foreach ($plan->buyers as $k => $buyer):
                    if ($k === 'iClass') continue;
                    ?>
                    <div class="item-inf text-center_xs">
                        <div class="item-inf_t text-center_xs">
                            <?= Yii::t('app', 'Замовник') ?>
                        </div>
                        <div class="item-inf_txt text-center_xs">
                            <div class="row">
                                <div class="col-xs-12 col-sm-6 col-md-4 item-bl_t">
                                    <?= Yii::t('app', 'Назва замовника') ?>
                                </div>
                                <div class="col-xs-12 col-sm-6 col-md-8 item-bl_val"
                                     data-test-id="procuringEntity.name">
                                    <?= Html::encode(@$buyer->name) ?>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-12 col-sm-6 col-md-4 item-bl_t">
                                    <?= Yii::t('app', 'company.identifier') ?>
                                </div>
                                <div class="col-xs-12 col-sm-6 col-md-8 item-bl_val">
                                    <?= Html::encode(@$buyer->identifier->id) ?>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-12 col-sm-6 col-md-4 item-bl_t">
                                    <?= Yii::t('app', 'company.scheme') ?>
                                </div>
                                <div class="col-xs-12 col-sm-6 col-md-8 item-bl_val"
                                     data-test-id="procuringEntity.address">
                                    <?= Html::encode(@$buyer->identifier->scheme) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php } ?>

            <div class="item-inf text-center_xs">
                <div class="item-inf_t text-center_xs">
                    <?= Yii::t('app', 'Закупівельник') ?>
                </div>
                <div class="item-inf_txt text-center_xs">
                    <div class="row">
                        <div class="col-xs-12 col-sm-6 col-md-4 item-bl_t">
                            <?= Yii::t('app', 'Назва закупівельника') ?>
                        </div>
                        <div data-test-id="procuringEntity.identifier.legalName"
                             class="col-xs-12 col-sm-6 col-md-8 item-bl_val" data-test-id="procuringEntity.name">
                            <?= Html::encode(@$plan->procuringEntity->name) ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12 col-sm-6 col-md-4 item-bl_t">
                            <?= Yii::t('app', 'company.identifier') ?>
                        </div>
                        <div class="col-xs-12 col-sm-6 col-md-8 item-bl_val"
                             data-test-id="procuringEntity.identifier.id">
                            <?= Html::encode(@$plan->procuringEntity->identifier->id) ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12 col-sm-6 col-md-4 item-bl_t">
                            <?= Yii::t('app', 'company.scheme') ?>
                        </div>
                        <div data-test-id="procuringEntity.identifier.scheme"
                             class="col-xs-12 col-sm-6 col-md-8 item-bl_val">
                            <?= Html::encode(@$plan->procuringEntity->identifier->scheme) ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="item-inf text-center_xs">
                <div class="item-inf_t text-center_xs">
                    <?= Yii::t('app', 'Параметри закупiвлi') ?>
                </div>

                <div class="item-inf_txt text-center_xs">
            <!--Rationale-->
                    <? if (($plan->rationale->description)) { ?>
                    <div class="row">
                        <div class="col-xs-12 col-sm-6 col-md-4 item-bl_t">
                            <?= Yii::t('app', 'Обгрунтування закупівлі') ?>
                        </div>
                        <div class="col-xs-12 col-sm-6 col-md-8 item-bl_val">
                            <?= Html::encode($plan->rationale->description); ?>
                        </div>
                    </div>
                        <div class="row">
                            <div class="col-xs-12 col-sm-6 col-md-4 item-bl_t">
                                <?= Yii::t('app', 'Дата публікації обґрунтування') ?>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-8 item-bl_val">
                                <?= Html::encode(date('d/m/Y H:i:s', strtotime($plan->rationale->date))) ?>
                            </div>
                        </div>

                    <? } ?>
            <!--Rationale-->
                    <div class="row">
                        <div class="col-xs-12 col-sm-6 col-md-4 item-bl_t">
                            <?= Yii::t('app', 'Назва плану') ?>
                        </div>
                        <div data-test-id="budget.description" class="col-xs-12 col-sm-6 col-md-8 item-bl_val">
                            <?= Html::encode($plan->budget->description); ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12 col-sm-6 col-md-4 item-bl_t">
                            <?= Yii::t('app', 'Бюджет') ?>
                        </div>
                        <div class="col-xs-12 col-sm-6 col-md-8 item-bl_val">
                        <span data-test-id="budget.amount"><?= Html::encode($plan->budget->getAmount()) ?>
                            <? //= Html::encode(\app\models\tenderModels\Value::getPDV()[(int)$plan->budget->valueAddedTaxIncluded]) ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12 col-sm-6 col-md-4 item-bl_t">
                            <?= Yii::t('app', 'Валюта') ?>
                        </div>
                        <div data-test-id="budget.currency" class="col-xs-12 col-sm-6 col-md-8 item-bl_val">
                            <?= Html::encode($plan->budget->getCurrency()); ?>
                        </div>
                    </div>
                    <? if (($plan->tender->procurementMethod . '_' . $plan->tender->procurementMethodType) === 'open_closeFrameworkAgreementUA') { ?>
                        <div class="row">
                            <div class="col-xs-12 col-sm-6 col-md-4 item-bl_t">
                                <?= \Yii::t('app', 'Початок дії плану') ?>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-8 item-bl_val">
                                <?= (preg_match('/^\d{4}$/', $plan->budget->period->startDate)) ? Html::encode($plan->budget->period->startDate) : Html::encode(substr($plan->budget->period->startDate, 6, 4)) ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-12 col-sm-6 col-md-4 item-bl_t">
                                <?= \Yii::t('app', 'Кінець строку дії плану') ?>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-8 item-bl_val">
                                <?= (preg_match('/^\d{4}$/', $plan->budget->period->endDate)) ? Html::encode($plan->budget->period->endDate) : Html::encode(substr($plan->budget->period->endDate, 6, 4)) ?>
                            </div>
                        </div>
                    <? } else { ?>
                        <div class="row">
                            <div class="col-xs-12 col-sm-6 col-md-4 item-bl_t">
                                <?= \Yii::t('app', 'Початок дії плану') ?>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-8 item-bl_val">
                                <?= Html::encode(substr($plan->budget->period->startDate, 0, 10)) ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-12 col-sm-6 col-md-4 item-bl_t">
                                <?= \Yii::t('app', 'Кінець строку дії плану') ?>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-8 item-bl_val">
                                <?= Html::encode(substr($plan->budget->period->endDate, 0, 10)) ?>
                            </div>
                        </div>
                    <? } ?>
                    <div class="row">
                        <div class="col-xs-12 col-sm-6 col-md-4 item-bl_t">
                            <?= Yii::t('app', 'Планова дата старту процедури') ?>
                        </div>
                        <div class="col-xs-12 col-sm-6 col-md-8 item-bl_val">
                            <?= Html::encode($plan->tender->tenderPeriod->getStartDate($plan)) ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12 col-sm-6 col-md-4 item-bl_t">
                            <?= Yii::t('app', 'Примiтки') ?>
                        </div>
                        <div class="col-xs-12 col-sm-6 col-md-8 item-bl_val">
                            <?= Html::encode($plan->budget->notes) ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12 col-sm-6 col-md-4 item-bl_t">
                            <?= Yii::t('app', 'Тип процедури') ?>
                        </div>
                        <div class="col-xs-12 col-sm-6 col-md-8 item-bl_val">
                            <?php
                            if ($plan->tender->procurementMethod == '' && $plan->tender->procurementMethodType != 'centralizedProcurement') {
                                echo \app\models\Plans::getPlanProcurementMethodView()[''];
                            } else {
                                echo \app\models\Plans::getPlanProcurementMethodView()[$plan->tender->procurementMethod . '_' . $plan->tender->procurementMethodType];
                            }
                            ?>
                        </div>
                    </div>

                    <div class="row">
                        <div data-test-id="classification.scheme" class="col-xs-12 col-sm-6 col-md-4 item-bl_t">
                            <?= Yii::t('app', 'Класифiкатор') ?>
                        </div>
                        <div class="col-xs-12 col-sm-6 col-md-8 item-bl_val">
                            <? //= $plan->classification->scheme ?>
                            <span data-test-id="classification.id"><?= $plan->classification->id ?></span>
                            <span data-test-id="classification.description"><?= $plan->classification->description ?></span>
                        </div>
                    </div>

                    <?php
                    $str = '';
                    $show_block = false;
                    foreach ($plan->additionalClassifications as $a => $additionalClassification) {
                        if ($a === 'iClass' || !$additionalClassification->scheme || $additionalClassification->scheme == 'NONE') continue;
                        $show_block = true;
                        if ($additionalClassification->id == '000') {
                            $str .= '<p>' . $additionalClassification->description . '</p>';
                        } elseif ($additionalClassification->id) {
                            $str .= '<p>Код за ' . Yii::t('app', 'dkcode_' . $additionalClassification->scheme) . ' - ' . $additionalClassification->id . ' - ' . $additionalClassification->description . '</p>';
                        }
                    }
                    ?>
                    <? if ($show_block) { ?>
                        <div class="row">
                            <div class="col-xs-12 col-sm-6 col-md-4 item-bl_t">
                                <?= Yii::t('app', 'Додаткова класифікація') ?>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-8 item-bl_val">
                                <?= $str ?>
                            </div>
                        </div>
                    <? } ?>


                    <?php
                    unset($plan->items['iClass']);
                    unset($plan->items['__EMPTY_ITEM__']);
                    if ($plan->items[0]->id == null) unset($plan->items[0]);
                    if (empty($plan->items)) { ?>
                        <div class="item-inf_sub-t">
                            <?= Yii::t('app', 'Предмет закупiвлi') ?>
                        </div>
                        <?= Html::tag('span', Yii::t('app', 'Дані не було додано')); ?>
                    <? } else {
                        /**
                         * @var integer $i
                         * @var \app\models\planModels\Item $item
                         */
                        foreach ($plan->items as $i => $item) { ?>
                            <div class="text-center_xs">

                                <div class="item-inf_sub-t">
                                    <?= Yii::t('app', 'Предмет закупiвлi') ?>
                                </div>

                                <div class="row">
                                    <div class="col-xs-12 col-sm-6 col-md-4 item-bl_t">
                                        <?= Yii::t('app', 'Предмет закупiвлi') ?>
                                    </div>
                                    <div class="col-xs-12 col-sm-6 col-md-8 item-bl_val"
                                         data-test-id="items.description">
                                        <?= Html::encode($item->description) ?>
                                    </div>
                                </div>


                                <div class="row">
                                    <div class="col-xs-12 col-sm-6 col-md-4 item-bl_t">
                                        <?= Yii::t('app', 'Кiлькiсть') ?>
                                    </div>
                                    <div class="col-xs-12 col-sm-6 col-md-8 item-bl_val">
                                        <span data-test-id="items.quantity"><?= Html::encode($item->quantity) ?></span>
                                        <?
                                        if (isset($item->unit->code)) {
                                            $res = \app\models\Unit::find()->where(['id' => $item->unit->code])->limit(1)->one()->name;
                                            if ($res) { ?>
                                                <span data-test-id="items.unit.name"
                                                      data-test-item-unit-code="<?= $item->unit->code ?>"> <?= Html::encode($res); ?></span>
                                            <?php }
                                        }
                                        ?>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-xs-12 col-sm-6 col-md-4 item-bl_t">
                                        <?= Yii::t('app', 'Кінцевий строк поставки товарів, виконання робіт чи надання послуг') ?>
                                    </div>
                                    <div class="col-xs-12 col-sm-6 col-md-8 item-bl_val"
                                         data-test-id="items.deliveryDate.endDate">
                                        <?= Html::encode(substr($item->deliveryDate->endDate, 0, 10)) ?>
                                    </div>
                                </div>


                                <div class="row">
                                    <div data-test-id="items.classification.scheme"
                                         class="col-xs-12 col-sm-6 col-md-4 item-bl_t">
                                        <?= $item->getAttributeLabel('classification') ?>
                                    </div>
                                    <div class="col-xs-12 col-sm-6 col-md-8 item-bl_val">
                                        <span data-test-id="items.classification.id"><?= Html::encode($item->classification->id) ?></span>
                                        -
                                        <span data-test-id="items.classification.description"><?= Html::encode($item->classification->description) ?></span>
                                    </div>
                                </div>
                                <? $arr_scheme = ['NONE', 'specialNorms', '000'];
                                if ($item->additionalClassifications) {
                                    foreach ($item->additionalClassifications as $add_class) {
                                        if ($add_class->scheme && !in_array($add_class->scheme, $arr_scheme)) { ?>
                                            <div class="row">
                                                <div class="col-xs-12 col-sm-6 col-md-4 item-bl_t">
                                                    <?= $item->getAttributeLabel('additionalClassifications') ?>
                                                </div>
                                                <div class="col-xs-12 col-sm-6 col-md-8 item-bl_val">
                                                    <?= 'Код за ' . Yii::t('app', 'dkcode_' . $add_class->scheme) . ' - '; ?>
                                                    <?= Html::encode($add_class->id) ?>
                                                    -
                                                    <?= Html::encode($add_class->description) ?>
                                                </div>
                                            </div>
                                        <? }
                                    }
                                } ?>
                            </div>
                        <? }
                    } ?>
                </div>
            </div>


            <div class="item-inf text-center_xs">
                <div class="item-inf_t text-center_xs">
                    <?= Yii::t('app', 'breakdown') ?>
                </div>
                <div class="item-inf_txt text-center_xs">

                    <? //формируем массив из последних версий файлов.
                    if (isset($plan->budget->breakdown['iClass'])) {
                        unset($plan->budget->breakdown['iClass']);
                    }
                    if (empty($plan->budget->breakdown)) {
                        echo Html::tag('span', Yii::t('app', 'Дані не було додано'));
                    } else {

                        foreach ($plan->budget->breakdown as $key => $breakdown) {
                            if ((!isset($breakdown->id) && $breakdown->id == '')) continue; ?>
                            <div class="row">
                                <div class="col-xs-12 col-sm-6 col-md-4 item-bl_t">
                                    <?= Yii::t('app', 'Назва джерела') ?>
                                </div>
                                <div class="col-xs-12 col-sm-6 col-md-8 item-bl_val">
                                    <?= Html::encode($breakdown->getTitle($breakdown->title)) ?>
                                </div>
                            </div>

                            <?php if ($breakdown->description) { ?>
                                <div class="row">
                                    <div class="col-xs-12 col-sm-6 col-md-4 item-bl_t">
                                        <?= Yii::t('app', 'Просто опис') ?>
                                    </div>
                                    <div class="col-xs-12 col-sm-6 col-md-8 item-bl_val">
                                        <?= Html::encode($breakdown->description) ?>
                                    </div>
                                </div>
                            <?php } ?>

                            <div class="row">
                                <div class="col-xs-12 col-sm-6 col-md-4 item-bl_t">
                                    <?= Yii::t('app', 'Cума, яка виділена для окремого джерела фінансування') ?>
                                </div>
                                <div class="col-xs-12 col-sm-6 col-md-8 item-bl_val">
                                    <?= Html::encode($breakdown->value->getAmount() . ' ' . $breakdown->value->getCurrency()) ?>
                                </div>
                            </div>
                            <br/>

                        <? }
                    } ?>
                </div>
            </div>


            <div class="item-inf text-center_xs">
                <div class="item-inf_t text-center_xs">
                    <?= Yii::t('app', 'Документацiя') ?>
                </div>
                <div class="item-inf_txt text-center_xs">

                    <? //формируем массив из последних версий файлов.

                    //echo '<pre>'; print_r($plan->documents); echo '</pre>';

                    if (isset($plan->documents['iClass'])) {
                        unset($plan->documents['iClass']);
                    }
                    if (empty($plan->documents)) {
                        echo Html::tag('span', Yii::t('app', 'Дані не було додано'));
                    } else {
                        /** @var \app\models\planModels\Document $document */

                        foreach ($plan->documents as $key => $document) {
                            if ((!isset($document->id) && $document->id == '')) continue;
                            if (!$document->url) {
                                $document->url = '/uploads/' . $document->realName;
                            }
                            ?>
                            <div class="row">
                                <div class="col-xs-12 col-sm-6 col-md-4 item-bl_t">
                                    <?= Yii::t('app', 'Назва документу') ?>
                                </div>
                                <div class="col-xs-12 col-sm-6 col-md-8 item-bl_val">
                                    <?= Html::a(Html::encode($document->title), Html::encode($document->url), ['target' => '_blank', 'download' => true]) ?>
                                </div>

                                <? if (count($document->previousVersions) > 1) {
                                    foreach ($document->previousVersions as $pkey => $previousDocument) {
                                        if ($pkey === 'iClass') continue; ?>
                                        <div class="col-xs-12 col-sm-offset-6 col-sm-6 col-md-offset-4 col-md-8 item-doc_link__old">
                                            <?= Html::a(Html::encode($previousDocument->title), Html::encode($previousDocument->url), ['class' => 'mk-link']); ?>
                                        </div>
                                    <? }
                                } ?>
                            </div>
                        <? }
                    } ?>
                </div>
            </div>
            <? if ($published && !$plans->isOwner() && $plan->existsSignatureDocument()) {
                if (\Yii::$app->params['EDS 2.0']) {
                    echo Html::button(Yii::t('app', 'check.sign.ecp') . ' (2.0)', [
                        'class' => 'js_check-sign mk-btn mk-btn_default',
                        'tid' => $plan->id,
                        'data-url' => \app\components\Api::getUrl() . 'plans/' . $plan->id,
                    ]);
                } else {
                    echo Html::button(Yii::t('app', 'check.sign.ecp'), [
                        'class' => 'js_check-sign mk-btn mk-btn_default',
                        'tid' => $plan->id,
                        'data-url' => \app\components\Api::getUrl() . 'plans/' . $plan->id,
                        'data-placeholder-id' => '#check-sign-place',
                        'data-post-sign' => 'postSignPlan',
                        'data-loading-text' => Yii::t('app', 'Зачекайте'),
                    ]);
                }
                echo Html::tag('div', '', ['id' => 'check-sign-place']);
                ?>

                <div class="row">
                    <div class="col-sm-12">
                        <div class="message message_success" id="checkSuccess" style="display: none">Підпис успішно
                            перевірено
                        </div>
                        <div class="message message_danger" id="checkError" style="display: none">Підпис невірний</div>
                        <div class="message message_danger overflow-auto" id="checkDiff" style="display: none"></div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12">
                        <div class="item-inf_txt"
                        <div id="signersInfo" style="display: none; margin-bottom: 30px;"></div>
                    </div>
                </div>

            <? } ?>
            <?php if ($plans->isOwner()) { ?>
                <div class="info-block">
                    <div class="info-block-content">
                        <div class="row text-center">

                            <?php if ($plan->canEdit($plans)) { ?>
                                <?= Html::a(Yii::t('app', 'Edit plan'), ['/buyer/plan/update', 'id' => $plans->id], ['class' => 'mk-btn mk-btn_accept']) ?>
                            <?php } ?>

                            <? if ($plans->status == 'draft') { ?>
                                <?= Html::a(Yii::t('app', 'Delete plan draft'),
                                    ['/buyer/plan/delete', 'id' => $plans->id], [
                                        'class' => 'mk-btn mk-btn_danger',
                                        'data-confirm' => Yii::t('app', 'Продовжити видалення плану?'),
                                    ]); ?>
                            <? }
                            if ($plan->canComplete($plans) && $plan->checkSign($plans)) {
                                echo Html::a($plan->getCompleteButtonName(),
                                    ['/buyer/plan/complete', 'id' => $plans->id], [
                                        'class' => 'mk-btn mk-btn_danger',
                                        'data' => [
                                            'confirm' => Yii::t('app', 'Завершити план? Увага! Після переведення рядку плану закупівлі в статус "Оголошено  тендер" ви не зможете оголосити тендер з даного рядка плану та зв\'язати рядок плану і тендер.'),
                                            'method' => 'post',
                                        ]
                                    ]);
                            }
                            if ($plan->canSendECP($plans)) {

                                if (\Yii::$app->params['EDS 2.0']) {

                                    $prefix = '';
//                            if ($plan->budget->amount >= 50000) {
//                                $prefix = '_50k';
//                            }
//                            if (Yii::$app->user->identity->company->customer_type === 'other') {
//                                $prefix = '';
//                            }

                                    if (Yii::$app->user->identity->company->customer_type === 'other' && in_array($plan->tender->procurementMethod . '_' . $plan->tender->procurementMethodType, ['open_belowThreshold']) && in_array($plan->status, ['draft'])) {
                                        yii\widgets\ActiveForm::begin(['options' => ['style' => 'display: inline-block;']]);
                                        echo Html::hiddenInput('pid', $plan->id);
                                        echo Html::submitButton(Yii::t('app', 'Активувати без накладання КЕП'), ['class' => 'mk-btn mk-btn_default', 'name' => 'activate_without_eds', 'id' => 'activate_without_eds']);
                                        yii\widgets\ActiveForm::end();
                                    }

                                    echo Html::button(Yii::t('app', 'Накласти КЕП'), [
                                        'class' => 'sign_btn mk-btn mk-btn_default',
                                        'data-target' => 'plan',
                                        'data-target_id' => $plan->id,
                                        'data-url' => \app\components\Api::getUrl() . 'plans/' . $plan->id,
                                        'data-send_url' => '/buyer/tender/ecp',
                                        'data-tender-method' => $plan->tender->procurementMethod . '_' . $plan->tender->procurementMethodType . $prefix,
                                    ]);
                                } else {
                                    echo Html::button(Yii::t('app', 'Накласти КЕП'), [
                                            'class' => 'sign_btn mk-btn mk-btn_default',
                                            'tid' => $plan->id,
                                            'data-url' => \app\components\Api::getUrl() . 'plans/' . $plan->id,
                                            'data-modal-title' => Yii::t('app', 'Робота з підписом'),
                                            'data-modal-message' => str_replace('"', '&quot;', Yii::t('app', 'sign.modal.message.for.plan')),
                                            'data-placeholder-id' => '#sign_block',
                                            'data-post-sign' => 'postSignPlan',
                                            'data-test-id' => 'SignDataButton',
                                            'data-loading-text' => Yii::t('app', 'Зачекайте')]
                                    );
                                }
                            } ?>
                        </div>
                        <div id="sign_block"></div>
                        <div id="sign-widget-parent"></div>
                        <?php if ($plan->canCreateTenderFromPlan($plans) && !$plan->checkSign($plans)) { ?>
                            <div class="">
                                <div class="message message_warning">
                                    <?= Yii::t('app', 'Увага, план відредаговано внесенням нових змін, щоб зміни були опубліковані на веб-порталі ProZorro, обов\'язково накладіть КЕП') ?>
                                </div>
                            </div>
                        <? } ?>
                    </div>
                </div>
            <? } ?>

            <?php if (Companies::canGetObjectByTransferToken($plans)) {
                echo $this->render('@app/views/tender/_transfer_form', [
                    'model' => $plans,
                    'type' => 'plans'
                ]);
            } ?>

            <? if (Companies::canGetTransferToken($plans)) {
                echo $this->render('@app/views/tender/_transfer_login_form');
            } ?>

            <?= $this->render('_nav_block', [
                'plan' => $plan,
                'plans' => $plans,
            ]); ?>
        </div>
    </div>
<?
$this->registerJsFile(yii\helpers\Url::to('@web/js/jsondiffpatchlib.js'), ['position' => yii\web\View::POS_END, 'depends' => 'yii\web\JqueryAsset']);
$this->registerCssFile(yii\helpers\Url::to('@web/css/jsondiffpatchlib.css'));
