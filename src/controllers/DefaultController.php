<?php

namespace yozh\import\controllers;

use Yii;
use yii\web\UploadedFile;
use yii\web\Response;
use yozh\base\controllers\DefaultController as Controller;
use yozh\import\models\UploadForm;
use yozh\product\models\ProductModel;
use yozh\properties\PropertiesBehavior;
use yozh\taxonomy\models\Taxonomy;
use yozh\properties\models\PropertiesModel;

class DefaultController extends Controller
{
	
	const PRODUCT_CLASSNAME    = 'yozh\product\models\ProductModel';
	const VOCABULARY_CLASSNAME = 'yozh\taxonomy\models\Taxonomy';
	const VOCABULARY_NAME      = 'Category';
	
	const LINES_LIMIT  = 100;
	const EXCEED_LIMIT = 'exceed_limit';
	const EOF          = 'eof';
	const EMPTY_LINE   = 'empty';
	const PROCESSING   = 'processing';
	
	const STATUS_FAIL   = false;
	const STATUS_EXISTS = 'exists';
	const STATUS_INSERT = 'insert';
	const STATUS_UPDATE = 'update';
	const STATUS_ON     = 'on';
	const STATUS_OFF    = 'off';
	const STATUS_DELETE = 'delete';
	
	
	public function actionIndex()
	{
		
		$Model = new UploadForm( [ 'extensions' => 'csv' ] );
		
		return $this->render( 'index', [
			'Model' => $Model,
		] );
	}
	
	public function actionUpload()
	{
		
		if( Yii::$app->request->isAjax ) {
			
			$Model = new UploadForm( [ 'extensions' => 'csv' ] );
			
			$Model->files = UploadedFile::getInstance( $Model, 'files' );
			
			$path = 'uploads/';
			
			if( ( $rootNode = ( static::VOCABULARY_CLASSNAME )::find( [ 'name' => static::VOCABULARY_NAME ] )->one() ) && $Model->upload( $path ) ) {
				
				$file = $Model->files;
				
				$rows     = 0;
				$spl_file = new \SplFileObject( $path . $file->name );
				
				while( $data = $spl_file->fgetcsv() ) {
					$rows++;
				}
				
				$session = Yii::$app->session;
				
				$session->set( 'import_config', [
					'file'       => $path . $file->name,
					'rows'       => $rows,
					'offset'     => 0,
					'limit'      => static::LINES_LIMIT,
					'categories' => array_flip( array_merge( [ 0 ], array_keys( Taxonomy::find()->all() ) ) ),
					'errors'     => [],
					'timer'      => microtime( true ),
				] );
				
				( static::VOCABULARY_CLASSNAME )::updateAll(
					[ 'active' => false ],
					'root = ' . $rootNode->root . ' AND id <> ' . $rootNode->id
				);
				
				Yii::$app->response->format = Response::FORMAT_JSON;
				
				return $rows;
			}
			
		}
		
		throw new \yii\web\NotFoundHttpException();
		
	}
	
	public function actionProcess()
	{
		if( Yii::$app->request->isAjax ) { //
			
			$session = Yii::$app->session;
			
			if( $import_config = $session->get( 'import_config' ) ) { //
				
				$result = $this->_process_csv( $import_config );
				
				switch( $result ) {
					case self::EXCEED_LIMIT :
						
						$rows   = $import_config['rows'];
						$offset = $import_config['offset'] = $import_config['offset'] + $import_config['limit'];
						
						$result = [
							'progress' => round( $offset / $rows * 100 ),
							'rows'     => $rows,
							'offset'   => $offset,
							'last sku' => $import_config['last sku'],
							'errors'   => $import_config['errors'],
						];
						
						$session->set( 'import_config', $import_config );
						
						break;
					
					case self::EOF :
						
						$result = [
							'progress' => 100,
							'rows'     => $import_config['rows'],
							'offset'   => $import_config['rows'],
						];
						
						//_fg_importcsv_ajax_finish_process();
						
						$time_end     = microtime( true );
						$time_elapsed = round( $time_end - $import_config['timer'], 3 );
						
						$session->destroy( 'import_config' );
						
						break;
				}
				
				Yii::$app->response->format = Response::FORMAT_JSON;
				
				return $result;
				
			}
			
		}
		
		throw new \yii\web\NotFoundHttpException();
		
	}
	
	public function _process_csv( &$import_config )
	{
		
		$file = new \SplFileObject( $import_config['file'] );
		$file->setFlags( \SplFileObject::READ_CSV );
		$file->seek( $import_config['offset'] );
		$categories = &$import_config['categories'];
		
		$row     = 1;
		$process = true;
		$specs   = [];
		
		while( $process === true || $process === self::EMPTY_LINE ) {
			
			$data = $this->_toUTF8( $file->fgetcsv() );
			//$data = $file->fgetcsv();
			
			if( !$data ) {
				$process = self::EOF;
				break;
			}
			
			if( count( $data ) < 2 ) {
				$process = self::EMPTY_LINE;
				continue;
			}
			
			$type = $data[0];
			$id   = $data[1];
			
			try {
				
				switch( $type ) {
					
					case 'group' :
						
						$result = $this->_process_csv_group( $data, $categories );
						
						break;
					
					case 'position' :
						
						$result = $this->_process_csv_product( $data, $specs );
						
						$import_config['last sku'] = $data[1];
						
						break;
					
					default:
				}
				
			} catch( \Exception $e ) {
				$import_config['errors'][ $type ][ $data[1] ] = $e->getMessage();
			}
			
			$row++;
			
			if( $row > $import_config['limit'] ) {
				$process = self::EXCEED_LIMIT;
				break;
			}
			
		}
		
		if( isset( $specs['result'] ) && is_array( $specs['result'] ) ) {
			
			//_fg_process_csv_specs( $specs['result'] );
			
		}
		
		return $process;
		
	}
	
	protected function _toUTF8( $data )
	{
		
		if( is_array( $data ) ) {
			
			foreach( $data as $key => $value ) {
				$data[ $key ] = $this->_toUTF8( $value );
			}
			
			return $data;
		}
		else if( is_string( $data ) ) {
			
			return mb_convert_encoding( $data, 'UTF-8', 'WINDOWS-1251' );
			
		}
		else {
			return $data;
		}
		
	}
	
	protected function _process_csv_group( $row_data, &$categories )
	{
		$vid        = 1;
		$modelClass = static::VOCABULARY_CLASSNAME;
		
		$tid  = $row_data[1];
		$name = $row_data[3];
		
		$group[ $tid ] = $row_data;
		
		if( $row_data[2] ) {
			
			$group[ $row_data[2] ]['items'][ $tid ] = &$group[ $tid ];
			
			$pid = $row_data[2];
		}
		else {
			$pid = $vid;
		}
		
		if( !$node = $modelClass::findOne( $tid ) ) {
			
			$node = new $modelClass( [
				'id' => $tid,
			] );
			
		}
		
		$node->name      = $name;
		$node->active    = true;
		$node->collapsed = true;
		
		if( $parent = $modelClass::findOne( $pid ) ) {
			
			$node->appendTo( $parent );
			$node->save();
			unset( $categories[ $tid ] );
			
			return $node;
		}
		
		return false;
		
	}
	
	protected function _process_csv_product( $row_data, &$specs = [] )
	{
		
		$modelClass = static::PRODUCT_CLASSNAME;
		
		$id   = $row_data[1];
		$name = $row_data[3];
		$tid  = $row_data[2];
		
		if( $id == 903210 ) {
			$trap = 1;
		}
		
		$specs = [];
		
		// формирование характеристик
		if( $row_data[6] && $spec = explode( "\n", $row_data[6] ) ) {
			
			foreach( $spec as $key => $data ) {
				
				$error = false;
				
				$data = explode( '=', $data );
				
				if( isset( $data[0] ) && isset( $data[1] ) ) {
					$specs[ trim( $data[0] ) ] = trim( $data[1] );
				}
				
			}
			
		}
		
		/**
		 * @var $product ProductModel
		 */
		if( !$product = $modelClass::findOne( $id ) ) {
			
			$product = new $modelClass( [
				'id' => $id,
			] );
			
		}
		
		$product->name        = $name;
		$product->taxonomy_id = $tid;
		$product->price       = $row_data[4];
		$product->units       = $row_data[5];
		
		if( $product->save() ) {
			
			if( $product->getBehavior( 'properties' ) && count( $specs ) ) {
				
				$product->deleteProperties();
				
				foreach( $specs as $name => $value ) {
					$product->setProperties( $name, $value, true, PropertyModel::INPUT_TYPE_STRING );
				}
			}
			
			return $product;
		}
		
		return false;
		
	}
	
	protected function _escapeRawData( $string )
	{
		return preg_replace( '/(?<![\\\])([\\;"])/', '\\\$1', $string );
	}
	
}
