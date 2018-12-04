<?php
/**
 * Created by PhpStorm.
 * User: bw_dev
 * Date: 21.02.2018
 * Time: 14:44
 */

namespace yozh\import\models;

use Yii;
use yii\base\Model;
use yii\web\UploadedFile;

class UploadForm extends Model
{
	
	public $maxFiles   = 1;
	public $extensions = '*';
	
	public $uploadPath;
	public $files;
	
	protected $_csv_mimetypes = [
		'text/csv',
		'text/plain',
		'application/csv',
		'text/comma-separated-values',
		'application/excel',
		'application/vnd.ms-excel',
		'application/vnd.msexcel',
		'text/anytext',
		'application/octet-stream',
		'application/txt',
	];
	
	public function rules( $rules = [], $update = false )
	{
		
		switch( $this->extensions ) {
			
			case 'csv':
				
				return [
					[ [ 'files' ], 'file',
						'skipOnEmpty'              => false,
						'checkExtensionByMimeType' => false,
						'extensions'               => 'csv',
						//'mimeTypes' => $this->_csv_mimetypes,
						'maxFiles'                 => $this->maxFiles,
					],
				];
				
				break;
			
			default:
				
				return [
					[ [ 'files' ], 'file', 'skipOnEmpty' => false, 'extensions' => $this->extensions, 'maxFiles' => $this->maxFiles ],
				];
			
		}
		
	}
	
	public function upload( $path = '' )
	{
		
		if( Yii::$app->request->isPost ) {
			
			if( $this->maxFiles != 1 ) { //
				$this->files = UploadedFile::getInstances( $this, 'files' );
			}
			else { //
				$this->files = UploadedFile::getInstance( $this, 'files' );
			}
			
			if( $this->validate() ) {
				
				if( $this->maxFiles != 1 ) { //
					$files = $this->files;
				}
				else { //
					$files = [ $this->files ];
				}
				
				foreach( $files as $file ) {
					
					$file->saveAs( $path . $file->name );
					
				}
				
				return true;
				
			}
		}
		
		return false;
		
	}
}
