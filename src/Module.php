<?php

namespace yozh\import;

use yozh\base\Module as BaseModule;

class Module extends BaseModule
{

	const MODULE_ID = 'import';
	
    public $controllerNamespace = 'yozh\\' . self::MODULE_ID . '\controllers';

}
