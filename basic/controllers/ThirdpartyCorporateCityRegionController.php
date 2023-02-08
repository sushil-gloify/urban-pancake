<?php

namespace app\controllers;

use Yii;
use app\api_v3\v3\models\ThirdpartyCorporateCityRegion;
use app\api_v3\v3\models\ThirdpartyCorporateLuggagePriceCity;
use app\api_v3\v3\models\ThirdpartyCorporateCityRegionSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use app\models\ThirdpartyCorporateDiscountPriceRegion;
use app\models\ThirdpartyCorporateDiscountPriceRegionSearch;

/**
 * ThirdpartyCorporateCityRegionController implements the CRUD actions for ThirdpartyCorporateCityRegion model.
 */
class ThirdpartyCorporateCityRegionController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all ThirdpartyCorporateCityRegion models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new ThirdpartyCorporateCityRegionSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single ThirdpartyCorporateCityRegion model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new ThirdpartyCorporateCityRegion model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new ThirdpartyCorporateCityRegion();
        $model->created_on = date('Y-m-d H:i:s');
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->thirdparty_corporate_city_id]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing ThirdpartyCorporateCityRegion model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $regionPrice = ThirdpartyCorporateLuggagePriceCity::find()->where(['thirdparty_corporate_city_id' => $id])->one();
        if($regionPrice){
            $regionPrice = ThirdpartyCorporateLuggagePriceCity::find()->where(['thirdparty_corporate_city_id' => $id])->one();
        }else{
            $regionPrice = new ThirdpartyCorporateLuggagePriceCity();
        }

        if ($model->load(Yii::$app->request->post())) {
            $regionPrice->bag_price = Yii::$app->request->post()['ThirdpartyCorporateLuggagePriceCity']['bag_price'];
            $regionPrice->thirdparty_corporate_city_id = $id;
            $regionPrice->status = 1;
            $regionPrice->created_on = date('Y-m-d H:i:s');
            if($regionPrice->save(false)){
                // return $this->redirect(['thirdparty-corporate/index']);
                return $this->redirect(['thirdparty-corporate-airports/airport-index','id' => $_POST['ThirdpartyCorporateCityRegion']['thirdparty_corporate_id']]);
            }
        } else {
            return $this->render('update', [
                'model' => $model,
                'priceModel' => $regionPrice,
            ]);
        }
    }

    /**
     * Deletes an existing ThirdpartyCorporateCityRegion model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the ThirdpartyCorporateCityRegion model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return ThirdpartyCorporateCityRegion the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = ThirdpartyCorporateCityRegion::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    public function actionDiscountUpdate($id)
    {
        $model = $this->findModel($id);
        $regionPrice = ThirdpartyCorporateDiscountPriceRegion::find()->where(['thirdparty_corporate_region_id' => $id])->one();
        if($regionPrice){
            $regionPrice = ThirdpartyCorporateDiscountPriceRegion::find()->where(['thirdparty_corporate_region_id' => $id])->one();
        }else{
            $regionPrice = new ThirdpartyCorporateDiscountPriceRegion();
        }

        if ($model->load(Yii::$app->request->post())) {
            $regionPrice->bag_price = Yii::$app->request->post()['ThirdpartyCorporateDiscountPriceRegion']['bag_price'];
            $regionPrice->thirdparty_corporate_region_id = $id;
            $regionPrice->status = 1;
            $regionPrice->created_on = date('Y-m-d H:i:s');
            if($regionPrice->save(false)){
                // return $this->redirect(['thirdparty-corporate/index']);
                return $this->redirect(['thirdparty-corporate-airports/airport-index','id' => $_POST['ThirdpartyCorporateCityRegion']['thirdparty_corporate_id']]);
            }
        } else {
            return $this->render('update', [
                'model' => $model,
                'priceModel' => $regionPrice,
            ]);
        }
    }
}
