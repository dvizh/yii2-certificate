<?php

namespace dvizh\certificate\widgets;

use dvizh\certificate\models\CertificateUse;

class CertificateWidget extends \yii\base\Widget
{

    public function init()
    {
        parent::init();

        \dvizh\certificate\assets\WidgetAsset::register($this->getView());
    }

    public function run()
    {
        $model = new CertificateUse;

        return $this->render('enter_form', ['model' => $model]);
    }
    
}