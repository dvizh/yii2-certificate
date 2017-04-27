<?php

use yii\helpers\Html;
use dvizh\certificate\assets\Asset;


Asset::register($this);

$this->title = 'Новый сертификат';
$this->params['breadcrumbs'][] = ['label' => 'Сертификаты', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="certificate-certificate-create">

    <?= $this->render('_form', [
        'model' => $model,
        'targetModelList' => $targetModelList,
        'clients' => $clients,
    ]) ?>

</div>
