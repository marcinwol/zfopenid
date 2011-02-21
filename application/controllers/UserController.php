<?php

class UserController extends Zend_Controller_Action {

    public function init() {
        /* Initialize action controller here */
    }

    public function indexAction() {
        // action body
    }

    public function loginAction() {

        // get an instace of Zend_Auth
        $auth = Zend_Auth::getInstance();

        // check if a user is already logged
        if ($auth->hasIdentity()) {
            $this->_helper->FlashMessenger('It seems you are already logged into the system ');
            return $this->_redirect('/index/index');
        }

        // if the user is not logged, the do the logging
        // $openid_identifier will be set when users 'clicks' on the account provider
        $openid_identifier = $this->getRequest()->getParam('openid_identifier', null);

        // $openid_mode will be sent after first query to the openid provider
        $openid_mode = $this->getRequest()->getParam('openid_mode', null);


        // do the first query to the openid provider
        if ($openid_identifier && null === $openid_mode) {

            $propertiesToRequest = array("email" => true);

            if ('https://www.google.com/accounts/o8/id' == $openid_identifier ||
                    'http://me.yahoo.com/' == $openid_identifier  ) {
                $ext = new My_OpenId_Extension_AttributeExchange($propertiesToRequest);
            }  else {
                $ext = new Zend_OpenId_Extension_Sreg($propertiesToRequest);
            }


            if ('https://www.facebook.com' == $openid_identifier) {
                $appId = '184175234953062';
		$secret = '18caeb8fe4c163b91338f4e4ea9eb0c5';
		$redirectUri = 'http://localhost.com/houseshare/public/login/';
		$scope = 'email';
                $adapter = new My_Auth_Adapter_Facebook($appId, $secret, $redirectUri, $scope);
                
            } else {
                $adapter = new Zend_Auth_Adapter_OpenId($openid_identifier);
                $adapter->setExtensions($ext);
            }


            // here a user is redirect to the provider for loging
            $result = $auth->authenticate($adapter);

            // the following two lines should never be executed unless the redirection faild.
            $this->_helper->FlashMessenger('Redirection faild');
            return $this->_redirect('/index/index');
        } else if ($openid_mode) {
            // this will be exectued after provider redirected the user back to us
            
            $openIDadapter = new Zend_Auth_Adapter_OpenId();

            $result = $auth->authenticate($openIDadapter);
            
//            var_dump($_GET);
//            var_dump($ext->getProperties());return;


            if ($result->isValid()) {
                $this->_helper->FlashMessenger('Successful OpenID authentication');
                $auth->getStorage()->write($this->getRequest()->getParams());
                return $this->_redirect('/index/index');
            } else {
                $this->_helper->FlashMessenger('Failed authentication');
                $this->_helper->FlashMessenger($result->getMessages());
                return $this->_redirect('/index/index');
            }
        }
    }

    public function logoutAction() {
        $auth = Zend_Auth::getInstance();
        $auth->clearIdentity();
    }

}

