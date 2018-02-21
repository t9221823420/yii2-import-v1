<?php

namespace yozh\import;

class AssetsBundle extends \yii\web\AssetBundle
{

    public $sourcePath = __DIR__ .'/../assets/';

    public $css = [
        'css/style.css'
    ];
	
	public $publishOptions = [
		//'forceCopy'       => true,
	];
	
}