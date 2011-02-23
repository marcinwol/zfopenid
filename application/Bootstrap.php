<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap {

    protected function _initDoctype() {
        $view = $this->bootstrap('view')->getResource('view');
        $view->doctype('XHTML1_STRICT');
    }

    protected function _initHelperPath() {
        $view = $this->bootstrap('view')->getResource('view');
        $view->setHelperPath(APPLICATION_PATH . '/views/helpers', 'My_View_Helper');
    }

    protected function _initAttributeExOpenIDPath() {
        $autoLoader = Zend_Loader_Autoloader::getInstance();

        $resourceLoader = new Zend_Loader_Autoloader_Resource(array(
                    'basePath' => APPLICATION_PATH,
                    'namespace' => 'My_',
                ));

        $resourceLoader->addResourceType('openidextension', 'openid/extension/', 'OpenId_Extension');
        $resourceLoader->addResourceType('authAdapter', 'auth/adapter', 'Auth_Adapter');

        $autoLoader->pushAutoloader($resourceLoader);
    }

     protected function _initAppKeysToRegistry() {

         $appkeys = new Zend_Config_Ini(APPLICATION_PATH . '/configs/appkeys.ini');
         Zend_Registry::set('keys', $appkeys);
        

     }

}

