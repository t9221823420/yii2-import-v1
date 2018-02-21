<?php

namespace yozh\import;

use yii\base\Module as BaseModule;



class Module extends BaseModule
{

	const MODULE_ID = 'import';
	
    public $controllerNamespace = 'yozh\\' . self::MODULE_ID . '\controllers';

}
