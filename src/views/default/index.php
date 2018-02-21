<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$vars = [];

?>

<?php $form = ActiveForm::begin( [ 'options' => [
	'id' => 'form',
	'enctype' => 'multipart/form-data',
	'multiple' => $model->maxFiles > 1 ? true : false,
] ] ) ?>

    <div class="form-group">
	    <?= Html::a(Yii::t('app', 'Category'), ['/taxonomy']) ?><br />
	    <?= Html::a(Yii::t('app', 'Product'), ['/product']) ?>
    </div>


<?= $form->field( $model, $model->maxFiles > 1 ? 'files[]' : 'files' )->fileInput( [ 'class' => 'btn btn-primary', 'id' => 'files' ] ) ?>

<?= Html::submitButton( Yii::t( 'app', 'Upload' ), [ 'class' => 'btn btn-primary hide', 'id' => 'submitButton' ] ) ?>

<?php ActiveForm::end() ?>
	
	<div id="progress" class="progress hide">
		<div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
	</div>


<?php $this->registerJs( $this->render('_js.php', ['section' => 'onload'] + $vars), $this::POS_READY ); ?>