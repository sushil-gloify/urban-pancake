<?php

namespace app\controllers;

use Yii;
use app\api_v3\v3\models\ThirdpartyCorporateAirports;
use app\api_v3\v3\models\ThirdpartyCorporateAirportsSearch;
use app\api_v3\v3\models\ThirdpartyCorporateCityRegionSearch;
use app\api_v3\v3\models\ThirdpartyCorporateLuggagePriceAirport;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;
use app\models\ThirdpartyCorporateDiscountPriceAirport;
use app\models\ThirdpartyCorporateDiscountPriceAirportSearch;
use app\models\ThirdpartyCorporateDiscountPriceRegion;
use app\models\ThirdpartyCorporateDiscountPriceRegionSearch;
use app\api_v3\v3\models\ThirdpartyCorporateCityRegion;
use app\api_v3\v3\models\ThirdpartyCorporateLuggagePriceCity;


/**
 * ThirdpartyCorporateAirportsController implements the CRUD actions for ThirdpartyCorporateAirports model.
 */
class ThirdpartyCorporateAirportsController extends Controller
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
     * Lists all ThirdpartyCorporateAirports models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new ThirdpartyCorporateAirportsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Lists all ThirdpartyCorporateAirports models.
     * @return mixed
     */
    public function actionAirportIndex($id = false)
    {
        $searchModel = new ThirdpartyCorporateAirportsSearch();
        $searchregionModel = new ThirdpartyCorporateCityRegionSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, $id);
        $dataProvider->pagination->pageSize=100;
        $regiondataProvider = $searchregionModel->search(Yii::$app->request->queryParams, $id);
        $regiondataProvider->pagination->pageSize=100;

        $searchdiscountairportModel = new ThirdpartyCorporateAirportsSearch();
        $discountairportdataProvider = $searchdiscountairportModel->corporatesearch(Yii::$app->request->queryParams, $id);
        $discountairportdataProvider->pagination->pageSize=100;

        $searchdiscountregionModel = new ThirdpartyCorporateCityRegionSearch();
        $discountregiondataProvider = $searchdiscountregionModel->discountsearch(Yii::$app->request->queryParams, $id);
        $discountregiondataProvider->pagination->pageSize=100;

        return $this->render('price-index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'searchregionModel' => $searchregionModel,
            'regiondataProvider' => $regiondataProvider,
            // for discount airport and city price
            'searchdiscountairportModel' => $searchdiscountairportModel,
            'discountairportdataProvider' => $discountairportdataProvider,
            'searchdiscountregionModel' => $searchdiscountregionModel,
            'discountregiondataProvider' => $discountregiondataProvider,
        ]);
    }

    public function actionAirportOutstationIndex($id = false)
    { 
        $model = new \app\models\BackgroundCsvImport();
        $searchModel = new \app\models\BackgroundCsvImportSearch();
        $dataProvider = $searchModel->OutstationSetPricesearch(Yii::$app->request->queryParams);

        if ($model->load(Yii::$app->request->post())) {
            $model->file_name = UploadedFile::getInstance($model, 'file_name');

            $model->file_size = $model->file_name->size;
            $model->import_type = 10;
            $saveFileName = $model->file_name->baseName . '_' . date('YmdHis');
            $model->file_name->saveAs('csv_import/csv_import_file/' . $saveFileName . '.' . $model->file_name->extension);

            $model->file_name = $saveFileName . '.' . $model->file_name->extension;
            $model->save(false);
            return $this->redirect(['airport-outstation-index','id'=>$id]);
        }


        return $this->render('outstation-price-index', [
            'model' => $model, 
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    } 


 
    /**
     * Displays a single ThirdpartyCorporateAirports model.
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
     * Creates a new ThirdpartyCorporateAirports model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new ThirdpartyCorporateAirports();
        $model->created_on = date('Y-m-d H:i:s');
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->thirdparty_corporate_airport_id]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing ThirdpartyCorporateAirports model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->thirdparty_corporate_airport_id]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing ThirdpartyCorporateAirports model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdatePrice($id)
    {
        $model = $this->findModel($id);
        $airportPrice = ThirdpartyCorporateLuggagePriceAirport::find()->where(['thirdparty_corporate_airport_id' => $id])->one();
        if($airportPrice){
            $airportPrice = ThirdpartyCorporateLuggagePriceAirport::find()->where(['thirdparty_corporate_airport_id' => $id])->one();
        }else{
            $airportPrice = new ThirdpartyCorporateLuggagePriceAirport();
        }

        if ($model->load(Yii::$app->request->post())) {
            $airportPrice->bag_price = Yii::$app->request->post()['ThirdpartyCorporateLuggagePriceAirport']['bag_price'];
            $airportPrice->thirdparty_corporate_airport_id = $id;
            $airportPrice->status = 1;
            $airportPrice->created_on = date('Y-m-d H:i:s');
            if($airportPrice->save(false)){
                return $this->redirect(['thirdparty-corporate-airports/airport-index','id' => $_POST['ThirdpartyCorporateAirports']['thirdparty_corporate_id']]);
                // return $this->redirect(['thirdparty-corporate/index']);
            }
        } else {
            return $this->render('_updatePrice', [
                'model' => $model,
                'priceModel' => $airportPrice,
            ]);
        }
    }

    /**
     * Deletes an existing ThirdpartyCorporateAirports model.
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
     * Finds the ThirdpartyCorporateAirports model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return ThirdpartyCorporateAirports the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = ThirdpartyCorporateAirports::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    public function actionUpdateCorporatePrice($id)
    {
        $model = $this->findModel($id);
        $airportPrice = ThirdpartyCorporateDiscountPriceAirport::find()->where(['thirdparty_corporate_airport_id' => $id])->one();
        if($airportPrice){
            $airportPrice = ThirdpartyCorporateDiscountPriceAirport::find()->where(['thirdparty_corporate_airport_id' => $id])->one();
        }else{
            $airportPrice = new ThirdpartyCorporateDiscountPriceAirport();
        }

        if ($model->load(Yii::$app->request->post())) {
            $airportPrice->bag_price = Yii::$app->request->post()['ThirdpartyCorporateDiscountPriceAirport']['bag_price'];
            $airportPrice->thirdparty_corporate_airport_id = $id;
            $airportPrice->status = 1;
            $airportPrice->created_on = date('Y-m-d H:i:s');
            if($airportPrice->save(false)){
                return $this->redirect(['thirdparty-corporate-airports/airport-index','id' => $_POST['ThirdpartyCorporateAirports']['thirdparty_corporate_id']]);
                // return $this->redirect(['thirdparty-corporate/index']);
            }
        } else {
            return $this->render('_updateCorporatePrice', [
                'model' => $model,
                'priceModel' => $airportPrice,
            ]);
        }
    }

    public function actionDeleteAirportRegionPrice($id, $corporateId,$type){
        if(!empty($type)){
            switch($type){
                case "airport" :
                    $record = ThirdpartyCorporateAirports::findOne(['thirdparty_corporate_airport_id' => $id]);
                    $delRecord = ThirdpartyCorporateLuggagePriceAirport::findOne(['thirdparty_corporate_airport_id' => $id]);
                    break;

                case "region" :
                    $record = ThirdpartyCorporateCityRegion::findOne(['thirdparty_corporate_city_id' => $id]);
                    $delRecord = ThirdpartyCorporateLuggagePriceCity::findOne(['thirdparty_corporate_city_id' => $id]);
                    break;
                
                case "discount-airport" :
                    $record = ThirdpartyCorporateAirports::findOne(['thirdparty_corporate_airport_id' => $id]);
                    $delRecord = ThirdpartyCorporateDiscountPriceAirport::findOne(['thirdparty_corporate_airport_id' => $id]);
                    break;
                
                case "discount-region" :
                    $record = ThirdpartyCorporateCityRegion::findOne(['thirdparty_corporate_city_id' => $id]);
                    $delRecord = ThirdpartyCorporateDiscountPriceRegion::findOne(['thirdparty_corporate_region_id' => $id]);
                    break;

                default :
                    Yii::$app->session->setFlash('error', "Failed to delete Process!");
            }
            if(!empty($delRecord)){
                if($delRecord->delete()){
                    Yii::$app->session->setFlash('success', "Successfully delete price.");
                } else {
                    Yii::$app->session->setFlash('error', "Failed to delete Process!");
                }
            } else {
                Yii::$app->session->setFlash('warning', "Price not set for this.");
            }
            
        } else {

        }
        return $this->redirect(['airport-index','id'=>$corporateId]);
    }
}
