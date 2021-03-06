<?php

namespace dvizh\certificate\controllers;

use dvizh\certificate\models\CertificateToItem;
use Yii;
use dvizh\certificate\models\Certificate;
use dvizh\certificate\models\search\CertificateSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;

class CertificateController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => $this->module->adminRoles,
                    ]
                ]
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $searchModel = new CertificateSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        if(!$targetUser = \Yii::$app->getModule('certificate')->clientModel) {
            $targetUser = false;
        }

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'targetUser' => $targetUser,
        ]);
    }

    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    public function actionCreate()
    {
        $model = new Certificate();
        $targetModelList = [];

        if($clients = $this->module->clientModel) {
            $clients = $clients::find()->all();
        } else {
            $clients = [];
        }

        if ($this->module->targetModelList) {
            $targetModelList = $this->module->targetModelList;
        }
        $model->owner_id = \Yii::$app->user->id;
        $model->created_at = date('Y-m-d H:i:s');

        if ($model->load(Yii::$app->request->post())) {
            if (strlen($model->date_elapsed) > 0) {
                $model->date_elapsed = date('Y-m-d H:i:s', strtotime($model->date_elapsed));
            } else {
                $model->date_elapsed = null;
            }

            $targets = Yii::$app->request->post();

            $model->save();

            if (isset($targets['targetModels'])) {
                $this->saveCertificateToModel($targets['targetModels'], $model->id);
            }

            return $this->redirect(['index']);
        } else {
            return $this->render('create', [
                'model' => $model,
                'targetModelList' => $targetModelList,
                'clients' => $clients,
            ]);
        }
    }

    public function actionCreateWidget()
    {
        $model = new Certificate();

        $json = [];


        $model->owner_id = \Yii::$app->user->id;
        $model->created_at = date('Y-m-d H:i:s');

        if ($model->load(Yii::$app->request->post())) {

            if (strlen($model->date_elapsed) > 0) {
                $model->date_elapsed = date('Y-m-d H:i:s', strtotime($model->date_elapsed));
            } else {
                $model->date_elapsed = null;
            }

            $targets = Yii::$app->request->post();

            if ($model->save()) {
                if (isset($targets['targetModels'])) {
                    $this->saveCertificateToModel($targets['targetModels'], $model->id);
                }

                $json['result'] = 'success';
                $json['certificate'] = $model->code;
                
            } else {
                $json['result'] = 'fail';
                $json['errors'] = current($model->getFirstErrors());
            }

        }

        return json_encode($json);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $certificateItems = CertificateToItem::find()->where(['certificate_id' => $id])->all();
        $targetModelList = [];
        $items = [];
        $title = false;

        if($clients = $this->module->clientModel) {
            $clients = $clients::find()->all();
        } else {
            $clients = [];
        }

        if ($this->module->targetModelList) {
            $targetModelList = $this->module->targetModelList;
        }

        foreach ($certificateItems as $certificateItem) {
            $target_model = $certificateItem->target_model;
            if ($certificateItem->target_id == 0) {
                $title = $this->getModelTitle($certificateItem->target_model);
            }
            $target = $target_model::findOne($certificateItem->target_id);
            $items[] = ['[' . $certificateItem->target_model . '][' . $certificateItem->target_id . ']' =>
                [
                    'name' => ($title) ? $title : $target->name,
                    'model' => $certificateItem->target_model,
                    'model_id' => $certificateItem->target_id,
                    'amount' => $certificateItem->amount,
                ]
            ];
        }

        if ($model->load(Yii::$app->request->post())) {

            if (strlen($model->date_elapsed) > 0) {
                $model->date_elapsed = date('Y-m-d H:i:s', strtotime($model->date_elapsed));
            } else {
                $model->date_elapsed = null;
            }

            $targets = Yii::$app->request->post();

            $model->save();

            if (isset($targets['targetModels'])) {
                $this->saveCertificateToModel($targets['targetModels'], $model->id, $certificateItems);
            }

            return $this->redirect(['index']);

        } else {
            return $this->render('update', [
                'model' => $model,
                'targetModelList' => $targetModelList,
                'items' => $items,
                'clients' => $clients,
            ]);
        }
    }

    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    protected function findModel($id)
    {
        if (($model = Certificate::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    protected function saveCertificateToModel($productModels, $certificateId, $savedItems = null)
    {
        if ($productModels) {
            foreach ($productModels as $productModel => $modelItems) {
                foreach ($modelItems as $id => $value) {
                    $model = CertificateToItem::find()->where([
                        'certificate_id' => $certificateId,
                        'target_model' => $productModel,
                        'target_id' => $id,
                    ])->one();
                    if (!$model) {
                        $model = new CertificateToItem();
                        $model->certificate_id = $certificateId;
                        $model->target_model = $productModel;
                        $model->target_id = $id;
                        $model->amount = $value;
                        if ($model->validate() && $model->save()) {
                            // do nothing
                        }
                    } else {
                        if ($model->amount != $value) {
                            $model->amount = $value;
                            $model->update();
                        }
                    }
                }
            }
        }
    }

    public function actionAjaxDeleteTargetItem()
    {
        $target = Yii::$app->request->post();

        $model = CertificateToItem::find()->where([
            'certificate_id' => $target['data']['certificateId'],
            'target_model' => $target['data']['targetModel'],
            'target_id' => $target['data']['targetModelId'],
        ])->one();
        if ($model) {
            if ($model->delete()) {
                \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                return [
                    'status' => 'success',
                ];
            } else return [
                'status' => 'error',
            ];
        } else
            return [
                'status' => 'success',
            ];

    }

    protected function getModelTitle($model)
    {
        $targetModelList = $this->module->targetModelList;
        foreach ($targetModelList as $name => $targetModel) {
            if ($targetModel['model'] == $model) {
                return $name;
            }
        }
        return false;
    }
}
