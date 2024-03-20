<?php

use app\models\tenderModels\Value;
use app\widgets\Icon\Icon;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;


/**
 * @var $form      yii\widgets\ActiveForm
 * @var app\models\planModels\Plan $plan
 * @var app\models\Plans $plans
 * @var $persons    app\models\Persons
 * @var $id        int
 * @var $published bool
 */

$this->title = $plans->plan_cbd_id ? $plans->plan_cbd_id : $plans->id;
$this->title($this->title, ['{posttitle}' => substr($plans['description'], 0, 150), '{postid}' => $plan->planID]);
$this->description($this->title, ['{posttitle}' => substr($plans['description'], 0, 250), '{postid}' => $plan->planID]);

$fieldLabel = $plan->attributeLabels();
if ($published) {
    $this->registerJs('
var test_mode_reload = true;
', yii\web\View::POS_BEGIN);
}
//\Yii::$app->VarDumper->dump($plan->documents, 10, true, true);
?>
<div class="container">
    <h1>
        <? if ($plans->getIsNewRecord()) { ?>
            <?= Yii::t('app', 'Створити рядок плану закупівлі') ?>
        <? } else { ?>
            <?= Yii::t('app', 'Редагувати рядок плану закупівлі') ?>
        <? } ?>
    </h1>
    <div class="page-panel">
        <div class="row">
            <? $form = ActiveForm::begin([
                'id' => 'plan_create',
                'errorCssClass' => 'mk-invalid',
                'successCssClass' => 'mk-valid',
                'fieldConfig' => [
                    'template' => '<div class="col-xs-12 col-sm-4">{label}</div><div class="col-xs-12 col-sm-8">{input}{error}</div>',
                    'inputOptions' => ['class' => false],
                    'labelOptions' => ['class' => false],
                    'options' => [
                        'class' => 'field-wrapper',
                    ],
                ],
                'options' => [
                    'enctype' => 'multipart/form-data',
                    'data-documents-count' => empty($plan->documents) ? 1 : (count($plan->documents) + 1),
                ],
            ]); ?>
            <div class="plan_header">
                <div class="col-xs-12">
                    <div class="h2">
                        <?= Yii::t('app', 'Параметри плану') ?>
                    </div>
                </div>

                <div class="clearfix"></div>

                <div class="field-wrapper">
                    <div class="col-xs-12 col-sm-4">
                        <label>
                            <?= Yii::t('app', 'Тип процедури') ?>
                        </label>
                    </div>
                    <div class="col-xs-12 col-sm-8">
                        <div class="select-wp">
                            <?php
                            $planMethod = \app\models\Plans::getPlanProcurementMethod();
                             unset($planMethod['selective_priceQuotation']);
                            ?>
                            <?= Html::dropDownList('procurementMethod', $plan->tender->procurementMethod . '_' . $plan->tender->procurementMethodType, $planMethod,
                                [
                                    'class' => 'tender_method_select', //
                                ] /* + ($published ? ['disabled'=>'disabled'] : []) */); ?>
                            <? if ($published) {
                                //echo Html::hiddenInput('procurementMethod',$plan->tender->procurementMethod . '_' . $plan->tender->procurementMethodType);
                            } ?>
                        </div>
                        <div class="help-block"></div>
                    </div>
                </div>

                <div class="clearfix"></div>

                <input type="hidden" name="Plan[id]" id="plan_id" value="<?= $plan->id ?>">
                <input type="hidden" name="id" id="id" value="<?= $id ?>">

                <div class="field-wrapper czo_list hide required">
                    <div class="col-xs-12 col-sm-4">
                        <label>
                            <?= Yii::t('app', 'Перелiк ЦЗО') ?>
                        </label>
                    </div>
                    <div class="col-xs-12 col-sm-8">
                        <div class="select-wp">
                            <?= Html::dropDownList('Czo', $plan->procuringEntity->identifier->id, ArrayHelper::map(\app\models\Czo::find()->all(), 'identifier',
                                function ($model) {
                                    return $model['name_uk'] . ' - ' . $model['identifier'];
                                }), ['prompt' => 'Не вибрано', 'id' => 'czo_select']); ?>
                        </div>
                        <div class="help-block"></div>
                    </div>
                </div>

                <!--Rationale-->
                <div class="contact_block">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <?= Yii::t('app', 'Обгрунтування закупівлі') ?>
                    </div>
                    <div class="panel-body">
                        <?= $form->field($plan->rationale, 'description')
                            ->textarea(['name' => 'Plan[rationale][description]', 'rows' => '3']);?>
                    </div>
                </div>
                </div>

                <div class="clearfix"></div>
                <!--Rationale-->

                <div class="contact_block">
                    <div class="panel panel-default">

                        <div class="panel-heading">

                            <?= Yii::t('app', 'Контактна особа') ?> <small>(<?= Yii::t('app', 'замовник') ?>)</small>
                        </div>
                        <div class="panel-body">
<!--                            <div class="field-wrapper">-->
<!--                                <div class="col-xs-12 col-sm-4">-->
<!---->
<!--                                    <label for="contact-point-select">-->
<!--                                        --><?php //= Yii::t('app', 'Оберiть') ?>
<!--                                    </label>-->
<!--                                </div>-->
<!--                                <div class="col-xs-12 col-sm-8">-->
<!--                                    <div class="select-wp">-->
<!--                                        <select id="contact-point-select">-->
<!--                                            --><?php
//                                            echo Html::tag('option', Yii::t('app', 'choose.contact.point'));
//                                            /** @var Persons $person */
//                                            foreach ($persons as $key => $person) {
//                                                $fullName = $person->userSurname . ' ' . $person->userName . ' ' . $person->userPatronymic;
//                                                echo Html::tag('option', Html::encode($fullName), [
//                                                    'data-contact-point-name' => Html::encode($fullName),
//                                                    'data-contact-point-email' => Html::encode($person->email),
//                                                    'data-contact-point-telephone' => Html::encode($person->telephone),
//                                                    'data-contact-point-url' => Html::encode($person->url),
//                                                    'data-contact-point-name_en' => Html::encode($person->userSurname_en . ' ' . $person->userName_en . ' ' . $person->userPatronymic_en),
//                                                    'data-contact-point-availableLanguage' => Html::encode($person->availableLanguage),
//                                                    'selected' => $fullName == Html::encode($plan->buyers[$key]->contactPoint->name),
//                                                ]);
//                                            }
//                                            ?>
<!--                                        </select>-->
<!--                                    </div>-->
<!--                                    <div class="help-block"></div>-->
<!--                                </div>-->
<!--                            </div>-->
                            <div class="contact_group_wrapper">
                                <?php foreach ($plan->buyers as $key => $buyer):?>
<!---->
<!--                                --><?php //= $form->field($buyer->contactPoint, 'name')
//                                    ->textInput(['name' => "Plan[buyers][$key][contactPoint][name]"]); ?>
<!---->
<!--                                --><?php //= $form->field($buyer->contactPoint, 'email')
//                                    ->textInput(['name' => "Plan[buyers][$key][contactPoint][email]"]); ?>
<!---->
<!--                                --><?php //= $form->field($buyer->contactPoint, 'telephone')
//                                    ->textInput(['name' => "Plan[buyers][$key][contactPoint][telephone]"]); ?>
<!---->
<!--                                --><?php //= $form->field($buyer->contactPoint, 'url')
//                                    ->textInput(['name' => "Plan[buyers][$key][contactPoint][url]"]); ?>

                                    <!--                Адреса замовника - початок -->

                                    <div class="panel panel-default">
                                        <div class="address_fields_block">
                                            <div class="panel-heading">
                                                <?= Yii::t('app', 'Адреса замовника') ?> <small>(<?= Yii::t('app', 'замовник') ?>)</small>
                                            </div>
                                            <div class="panel-body">
                                                <div class="row">
                                                    <div class="col-xs-12">
                                                        <?= $form->field($buyer->address, 'countryName')->dropDownList(
                                                            \yii\helpers\ArrayHelper::map(
                                                                (new \yii\db\Query())
                                                                    ->select(['name'])
                                                                    ->from('countries')
                                                                    ->all(),
                                                                'name',
                                                                function ($model, $defaultValue) {
                                                                    return $model['name'];
                                                                }
                                                            ),
                                                            [
                                                                'prompt' => Yii::t('app', 'not select'),
                                                                'id' => 'address-country-name',
                                                                'onchange' => 'addressSelectRegion($(this))',
                                                                'name' => "Plan[buyers][$key][address][countryName]",
                                                                'value' => $buyer->address->countryName
                                                            ]); ?>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-xs-12">
                                                        <?= $form->field($buyer->address, 'region')->dropDownList(
                                                            $buyer->address->getRegionsByCountryName(),
                                                            [
                                                                'id' => 'address-region-select',
                                                                'class' => 'address-region-select',
                                                                'name' => "Plan[buyers][$key][address][region]",
                                                                'prompt' => Yii::t('app', 'not select'),
//                                                                'value' => $buyer->address->region
                                                            ]) ?>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-xs-12">
                                                        <?= $form->field($buyer->address, 'locality')
                                                            ->textInput([
                                                                'name' => "Plan[buyers][$key][address][locality]",
                                                                'id' => 'address-region-locality',
                                                                'value' => $buyer->address->locality

                                                            ]) ?>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-xs-12">
                                                        <?= $form->field($buyer->address, 'streetAddress')
                                                            ->textInput([
                                                                'name' => "Plan[buyers][$key][address][streetAddress]",
                                                                'id' => 'address-region-streetAddress',
                                                                //'value' => $buyer->address->streetAddress

                                                            ]) ?>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-xs-12">
                                                        <?= $form->field($buyer->address, 'postalCode')
                                                            ->textInput([
                                                                'name' => "Plan[buyers][$key][address][postalCode]",
                                                                'id' => 'address-region-postalCode',
                                                                'value' => $buyer->address->postalCode
                                                            ]) ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="clearfix"></div>

                                    <!--                Адреса замовника - кінець -->
                                <?php endforeach;?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="clearfix"></div>



                <?= $form->field($plan->budget, 'description')->textarea([
                    'name' => 'Plan[budget][description]',
                    'class' => 'textarea-resize_v',
                    'data-test-id' => 'budget.description'
                ]) ?>

                <div class="clearfix"></div>

                <?= $form->field($plan->budget, 'notes')->textarea([
                    'name' => 'Plan[budget][notes]',
                    'class' => 'textarea-resize_v',

                ]) ?>

                <div class="clearfix"></div>

                <?= $form->field($plan->budget, 'amount')
                    ->textInput([
                        'name' => 'Plan[budget][amount]',
                        'class' => 'tender_full_amount',
                        'data-test-id' => 'budget.amount'
                    ])
                    ->label($fieldLabel['amount']); ?>

                <div class="clearfix"></div>

                <div class="field-wrapper">
                    <div class="col-xs-12 col-sm-4">
                        <label>
                            <?= Yii::t('app', 'Валюта') ?>
                        </label>
                    </div>
                    <div class="col-xs-12 col-sm-8">
                        <div class="select-wp">
                            <?= Html::dropDownList('Plan[budget][currency]', $plan->budget->currency, $plan->budget->getListCurrencyForSelect(), [
                                'class' => 'currency'
                            ]); ?>
                        </div>
                        <div class="help-block"></div>
                    </div>
                </div>

                <div class="clearfix"></div>

                <?
                if ($plan->tender->tenderPeriod->startDate && !in_array($plan->tender->procurementMethodType, ['centralizedProcurement', 'closeFrameworkAgreementUA'])) {
                    $sDate = date('m/Y', strtotime(str_replace('/', '.', $plan->tender->tenderPeriod->startDate)));
                } elseif ($plan->tender->tenderPeriod->startDate && $plan->tender->procurementMethodType == 'closeFrameworkAgreementUA') {
                    $sDate = date('Y', strtotime(str_replace('/', '.', $plan->tender->tenderPeriod->startDate)));
                } elseif ($plan->tender->tenderPeriod->startDate && $plan->tender->procurementMethodType == 'centralizedProcurement') {
                    $sDate = date('d/m/Y', strtotime(str_replace('/', '.', $plan->tender->tenderPeriod->startDate)));
                }
                ?>
                <?= $form->field($plan->tender->tenderPeriod, 'startDate')
                    ->textInput([
                        'name' => 'Plan[tender][tenderPeriod][startDate]',
                        'class' => 'picker',
                        'value' => $sDate,
                    ]); ?>

                <!--                --><? //= $form->field($plan->budget, 'year',['options'=>['class'=>'js-no-py']])->textInput([
                //                    'name' => 'Plan[budget][year]',
                //                    'data-max' => 1,
                //                    'class' => 'picker_year']); ?>
                <!--                <div class="clearfix"></div>-->

                <div class="clearfix"></div>


                <?
                if ($plan->budget->period->startDate && $plan->tender->procurementMethodType == 'closeFrameworkAgreementUA') {
                    $sDate = date('Y', strtotime(str_replace('/', '.', $plan->budget->period->startDate)));
                } elseif ($plan->tender->tenderPeriod->startDate) {
                    $sDate = date('d/m/Y', strtotime(str_replace('/', '.', $plan->budget->period->startDate)));
                }
                ?>
                <?= $form->field($plan->budget->period, 'startDate', ['options' => ['class' => 'js-for-py2']])->textInput([
                    'name' => 'Plan[budget][period][startDate]',
                    'data-max' => 20,
                    'class' => 'picker_year',
                    'value' => $sDate
                ]); ?>
                <div class="clearfix"></div>

                <?
                if ($plan->budget->period->endDate && $plan->tender->procurementMethodType == 'closeFrameworkAgreementUA') {
                    $sDate = date('Y', strtotime(str_replace('/', '.', $plan->budget->period->endDate)));
                } elseif ($plan->budget->period->endDate) {
                    $sDate = date('d/m/Y', strtotime(str_replace('/', '.', $plan->budget->period->endDate)));
                }
                ?>
                <?= $form->field($plan->budget->period, 'endDate', ['options' => ['class' => 'js-for-py2']])->textInput([
                    'name' => 'Plan[budget][period][endDate]',
                    'data-max' => 20,
                    'class' => 'picker_year',
                    'value' => $sDate
                ]); ?>
                <div class="clearfix"></div>

                <?
                // выбор url классификатора
                // для additional-Classifications через $type
                if (strpos($name, "additional") === 0) {
                    $url_classificator = 'classificator/' . $type;
                } else {
                    if (!$published) {
                        // черновики c CPV сбрасываем код и описание классификатора
                        if ($classification['scheme'] == 'CPV') {
                            $plan->classification->description = '';
                            $plan->classification->id = '';
                        }
                        // черновики сбрасываем на дк021
                        $classification['scheme'] = 'ДК021';
                        $url_classificator = 'classificator/dk021';
                    } else {
                        if ($classification['scheme'] == 'CPV') {
                            $url_classificator = 'classificator/cpv';
                        } else {
                            $url_classificator = 'classificator/dk021';
                        }
                    }
                } ?>
                <?= $this->render('__classification', [    // plan:classification:id/description
                    'k' => '', 'type' => 'cpv', 'form' => $form,
                    'parentId' => '',
                    'name' => 'classification',
                    'no_head_select' => true,
                    'classification' => $plan->classification,
                    'url_classificator' => $url_classificator,
                    'data_type' => 'root',
                ]);
                ?>

                <div class="clearfix"></div>

                <div class="additionalClassifications_block plan_root_classifications">
                    <div class="field-wrapper">
                        <div class="col-xs-12 col-sm-4">
                            <label>
                                <?php echo Yii::t('app', 'additionalClassifications') ?>
                            </label>
                        </div>
                        <div class="col-xs-12 col-sm-8">
                            <?php
                            $code = $plan->additionalClassifications[0]->scheme . '_' . mb_strtolower(\yii\helpers\BaseInflector::transliterate($plan->additionalClassifications[0]->scheme));
                            $selectItems = array_merge(['' => 'Відсутня', '000' => Yii::t('app', 'undefined')], Yii::$app->params['DK_LIBS'] + Yii::$app->params['INN_LIBS']);
                            unset($selectItems['UA-ROAD_none'], $selectItems['INN/APN_none'], $selectItems['GMDN_gmdn'], $selectItems['UA-ROAD_road']);
                            ?>
                            <div class="select-wp">
                                <?= Html::dropDownList('Plan[additionalClassifications][0][dkType]', $plan->additionalClassifications[0]->dkType ? $plan->additionalClassifications[0]->dkType : $code,
                                    $selectItems+Yii::$app->params['DK_LIBS']+ Yii::$app->params['INN_LIBS'], [
                                        'class' => 'additionalClassifications_select',
                                        'data-type' => 'root',
                                    ]); ?>
                            </div>
                        </div>
                    </div>
                    <div class="clearfix"></div>

                    <div class="additionalClassifications_input additionalClassifications_input_main">
                        <?= $this->render('__dk_classification', [    // plan:additionalClassifications:0:id/description
                            'k' => '',
                            'type' => mb_strtolower(\yii\helpers\BaseInflector::transliterate($plan->additionalClassifications[0]->scheme)),
                            'form' => $form,
                            'parentId' => '',
                            'name' => 'additionalClassifications][0',
                            'classification' => $plan->additionalClassifications[0],]); ?>

                    </div>
                </div>


                <?

                for ($i = 1; $i < 4; $i++) {
                    if (!$plan->additionalClassifications[$i]) {
                        continue;
                    }
                    if ($plan->additionalClassifications[$i]->id == null && $i > 0) echo '<div class="hide hide_kekv">';
                    echo $this->render('__classification', [
                        'k' => $i, 'type' => 'kekv', 'form' => $form,
                        'parentId' => '',
                        'name' => 'additionalClassifications][' . $i,
                        'classification' => $plan->additionalClassifications[$i],
                        'url_classificator' => 'classificator/kekv',
                        'no_head_select' => null,
                    ]);
                    if ($plan->additionalClassifications[$i]->id == null && $i > 0) echo '</div>';
                } ?>

            </div>

            <div class="col-xs-12">
                <div class="text-right text-center_xs">
                    <button type="button" class="mk-btn mk-btn_default add_kekv_plan">
                        <?= Icon::i('plus-sign') . Yii::t('app', 'Add') ?>  <?= Yii::t('app', 'kekv') ?>
                    </button>
                </div>
            </div>

            <?php
            if (!empty($plans->tender)) {
                echo Html::hiddenInput('Plan[procuringEntity][is_changed]', $plan->procuringEntity->is_changed);
            }
            ?>

            <!--Адреса іншого закупівельника початок -->

            <div class="field-wrapper" id="plan-procuring-container">
                <div class="row">
                    <div class="col-xs-12">

                        <?= Html::checkbox('Plan[procuringEntity][is_changed]', $plan->procuringEntity->is_changed, [
                            'id' => 'procuring-checkbox', 'label' => Yii::t('app', 'Вказати iншого закупiвельника')
                        ]) ?>
                    </div>
                </div>

                <?= Html::hiddenInput('Plan[procuringEntity][kind]', 1) ?>

                <div class="procuring_fields_block hide">
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="message message_info">
                                <?= Yii::t('app', 'Зверніть увагу! Оголосити закупівлю зможе лише закупівельник, вказаний у річному плані закупівлі'); ?>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xs-12">
                            <?= $form->field($plan->procuringEntity, 'kind')->dropDownList(ArrayHelper::map(\app\models\CompanyCustomerType::find()->all(), 'id', 'name'),
                                [
                                    'name' => 'Plan[procuringEntity][kind]',
                                ])->label(Yii::t('app', 'Customer Type')); ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xs-12">
                            <?= $form->field($plan->procuringEntity->identifier, 'legalName')->textInput([
                                'name' => 'Plan[procuringEntity][identifier][legalName]',
                                'value' => $plan->procuringEntity->is_changed ? $plan->procuringEntity->identifier->legalName : ''
                            ]); ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xs-12">
                            <?= $form->field($plan->procuringEntity, 'name')->textInput([
                                'name' => 'Plan[procuringEntity][name]',
                                'value' => $plan->procuringEntity->is_changed ? $plan->procuringEntity->name : ''
                            ]); ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xs-12">
                            <?= $form->field($plan->procuringEntity->identifier, 'id')->textInput([
                                'name' => 'Plan[procuringEntity][identifier][id]',
                                'value' => $plan->procuringEntity->is_changed ? $plan->procuringEntity->identifier->id : ''
                            ]); ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xs-12">
                            <?= $form->field($plan->procuringEntity->identifier, 'scheme')->dropDownList(
                                [
                                    'UA-EDR' => Yii::t('app', 'Схема UA-EDR'),
                                    //'UA-IPN' => Yii::t('app', 'Схема UA-IPN'),
                                ],
                                ['prompt' => Yii::t('app', 'not select'),
                                    'name' => 'Plan[procuringEntity][identifier][scheme]',
                                    'value' => $plan->procuringEntity->is_changed ? $plan->procuringEntity->identifier->scheme : ''
                                ]); ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xs-12">
                            <?= $form->field($plan->procuringEntity->address, 'countryName')->dropDownList(
                                \yii\helpers\ArrayHelper::map(
                                    (new \yii\db\Query())
                                        ->select(['name'])
                                        ->from('countries')
                                        ->all(),
                                    'name',
                                    function ($model, $defaultValue) {
                                        return $model['name'];
                                    }
                                ),
                                [
                                    'prompt' => Yii::t('app', 'not select'),
                                    'onchange' => 'procuringSelectRegion($(this))',
                                    'id' => 'procuring-country-name',
                                    'name' => 'Plan[procuringEntity][address][countryName]',
                                    'value' => $plan->procuringEntity->is_changed ? $plan->procuringEntity->address->countryName : ''
                                ]); ?>
                        </div>
                    </div>


                    <div class="row">
                        <div class="col-xs-12">
                            <?= $form->field($plan->procuringEntity->address, 'region')->dropDownList(
                                $plan->procuringEntity->address->getRegionsByCountryName(),
                                [
                                    'id' => 'procuring-region-select',
                                    'class' => 'procuring-region-select',
                                    'name' => 'Plan[procuringEntity][address][region]',
                                    'prompt' => Yii::t('app', 'not select')
                                ]) ?>
                        </div>
                    </div>


                    <div class="row">
                        <div class="col-xs-12">
                            <?= $form->field($plan->procuringEntity->address, 'locality')
                                ->textInput(['name' => 'Plan[procuringEntity][address][locality]']) ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xs-12">
                            <?= $form->field($plan->procuringEntity->address, 'streetAddress')
                                ->textInput(['name' => 'Plan[procuringEntity][address][streetAddress]']) ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xs-12">
                            <?= $form->field($plan->procuringEntity->address, 'postalCode')
                                ->textInput(['name' => 'Plan[procuringEntity][address][postalCode]']) ?>
                        </div>
                    </div>


                </div>
            </div>

            <!--Адреса іншого закупівельника кінець -->

            <div class="info-block breakdown_block">
                <div class="col-xs-12">
                    <div class="h2">
                        <?= Yii::t('app', 'Джерело фінансування') ?>
                    </div>
                </div>

                <?php
                foreach ($plan->budget->breakdown as $k => $breakdown) {
                    if ($k === 'iClass') continue;
                    if ($k === '__EMPTY_BREAKDOWN__') echo '<div id="breakdown_new_element" style="display: none;">';
                    if ($breakdown->id == '') echo '<div class="hide">';
                    else $k++;

                    echo $this->render('_breakdown', [
                        'k' => $k,
                        'breakdown' => $breakdown,
                        'form' => $form,
                        //'typedk' => mb_strtolower(\yii\helpers\BaseInflector::transliterate($plan->additionalClassifications[0]->scheme)),
                    ]);
                    if ($k === '__EMPTY_BREAKDOWN__') echo '</div>';
                    if ($breakdown['id'] == '') echo '</div>';
                } ?>
            </div>


            <div class="row">
                <div class="col-xs-12">
                    <div class="text-right text-center_xs">
                        <button type="button" class="mk-btn mk-btn_default add_plan_breakdown">
                            <?= Icon::i('plus-sign') . Yii::t('app', 'add.breakdown.item') ?>
                        </button>
                    </div>
                </div>
            </div>


            <div class="panel panel-default js_block-need-tender-method-type" data-test-id="tender.documents.upload">
                <div class="panel-heading">
                    <?= Yii::t('app', 'Документацiя') ?>
                </div>
                <div class="panel-body">
                    <div class="document_block">
                        <div id="hidden_document_original" class="document-block" style="display: none">
                            <?= $this->render('_document', [
                                'form' => $form,
                                'document' => new \app\models\planModels\Document(),
                                'k' => '__EMPTY_DOC__',
//                                'lot_items' => [],
//                                'currentLotId' => '',
                            ]); ?>
                        </div>
                        <?php if (!empty($plan->documents)) {
                            foreach ($plan->documents as $d => $document) {
                                if ($d === 'iClass' || $d === '__EMPTY_DOC__') continue;
                                //if ($document->localRelatedItem ? $document->localRelatedItem === 'tender' : ($tenders->tender_type == 2 ? $document->documentOf == 'tender' : (in_array($document->documentOf, ['tender', 'item'])))) { ?>
                                <div class="document-block">
                                    <?= $this->render('_document', [
                                        'form' => $form,
                                        'document' => $document,
                                        'k' => $d,
                                    ]); ?>
                                </div>
                                <? // }
                            }
                        }
                        unset($tender->documents['iClass'], $tender->documents['__EMPTY_DOC__']);
                        ?>
                    </div>
                </div>
                <div class="panel-footer text-right">
                    <div class="uploadfile"><?= Yii::t('app', 'add file') ?></div>
                </div>
            </div>


            <div class="info-block items_block">
                <div class="col-xs-12">
                    <div class="h2">
                        <?= Yii::t('app', 'Специфiкацiя плану') ?>
                    </div>
                </div>
                <div class="col-xs-12">
                    <div class="h4">
                        <?= Yii::t('app', 'Надайте iнформацiю щодо предметiв закупiвлi, якi Ви маєте намiр прибдати в рамках даного плану') ?>
                    </div>
                </div>
                <?php
                if ($published) {
                    $plan->items['__EMPTY_ITEM__']->classification->scheme = $plan->classification->scheme;
                }
                foreach ($plan->items as $k => $item) {
                    if ($k === 'iClass') continue;
                    if ($k === '__EMPTY_ITEM__') echo '<div id="item_new_element" style="display: none;">';
                    if ($item->id == '') echo '<div class="hide">';
                    else $k++;

                    //include '_item.php';

                    if (!$published) {
                        // черновики c CPV сбрасываем код и описание классификатора
                        if ($classification['scheme'] == 'CPV') {
                            $item->classification->description = '';
                            $item->classification->id = '';
                        }
                        // черновики сбрасываем на дк021
                        $classification['scheme'] = 'ДК021';
                        $url_classificator = 'classificator/dk021';
                    } else {
                        if ($classification['scheme'] == 'CPV') {
                            $url_classificator = 'classificator/cpv';
                        } else {
                            $url_classificator = 'classificator/dk021';
                        }
                    }

                    echo $this->render('_item', [
                        'k' => $k,
                        'item' => $item,
                        'form' => $form,
                        'url_classificator' => $url_classificator,
                        //'typedk' => mb_strtolower(\yii\helpers\BaseInflector::transliterate($plan->additionalClassifications[0]->scheme)),
                    ]);
                    if ($k === '__EMPTY_ITEM__') echo '</div>';
                    if ($item['id'] == '') echo '</div>';
                } ?>
            </div>
            <div class="col-xs-12">
                <div class="text-right text-center_xs">
                    <button type="button" class="mk-btn mk-btn_default add_item_plan">
                        <?= Icon::i('plus-sign') . Yii::t('app', 'add.plan.item') ?>
                    </button>
                </div>
            </div>
            <div class="col-xs-12">
                <div class="text-center">
                    <? if ($published && (($plan->mode === 'test' && !Yii::$app->user->identity->test_mode) || (!$plan->mode && Yii::$app->user->identity->test_mode))) { ?>
                        <div class="message message_danger">
                            <?= Yii::t('app', 'Увага! Редагування заблоковано. Тестовий режим плану та користувача не співпадають') ?>
                        </div>
                    <? } else { ?>
                        <?= Html::submitButton(Yii::t('app', 'Cancel'), [
                            'class' => 'mk-btn mk-btn_danger',
                            'name' => 'cancel',
                            'id' => 'plan_cancel',
                            'data-loading-text' => Yii::t('app', 'Зачекайте'),
                            'data' => [
                                'confirm' => Yii::t('app', 'Are you sure you want to cancel this plan?'),
                                'method' => 'post',
                                'params' => [
                                    'cancel' => 'cancel',
                                ],
                            ],
                        ]) ?>
                        <? if (!$published) {
                            echo Html::submitButton(Yii::t('app', 'Save to draft'), [
                                'class' => 'mk-btn mk-btn_default',
                                'data-loading-text' => Yii::t('app', 'Зачекайте'),
                                'name' => 'drafts',
                                'id' => 'plan_drafts']);
                        } ?>
                        <?= Html::submitButton(Yii::t('app', 'Save and publish'),
                            [
                                'class' => 'mk-btn mk-btn_accept',
                                'name' => 'publish',
                                'data-loading-text' => Yii::t('app', 'Зачекайте'),
                            ]) ?>
                    <? } ?>
                </div>

            </div>
            <?php ActiveForm::end(); ?>
            <input type="hidden" id="plan_id" value="<?= isset($plans->id) ? $plans->id : '' ?>" name="Plan[planId]">
        </div>
    </div>
</div>


<?php

echo $this->render('classificator_modal');
//$this->registerJsFile(Url::to('@web/js/features.js'), ['position' => yii\web\View::POS_END, 'depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile(Url::to('@web/js/plan.js'), ['position' => yii\web\View::POS_END, 'depends' => 'yii\web\JqueryAsset']);
$this->registerCssFile(Url::to('@web/css/bootstrap-datetimepicker.css'));

$this->registerJs('

var ItemCount = ' . (count($plan->items) - 1) . ';
var breakdownCount = ' . (count($plan->budget->breakdown) - 1) . ';
var curLocale = "' . substr(Yii::$app->language, 0, 2) . '";
var AutoSaveTimer;

', yii\web\View::POS_END);

if (!empty($plans->tender)) {
    $this->registerJs('
$("#procuring-checkbox").prop("disabled", true);
$("#plan-procuring-container input, #plan-procuring-container select").prop("readonly", true);
$("#identifier-scheme option:not(:selected)").prop("disabled", true);

', yii\web\View::POS_END);
}
?>
<STYLE>
    .js-kekv-remove {
        position: absolute;
        top: 9px;
        right: 15px;
        cursor: pointer;
    }
</STYLE>
