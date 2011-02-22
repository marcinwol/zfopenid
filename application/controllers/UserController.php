<?php

class UserController extends Zend_Controller_Action {

    /**
     * Application keys from appkeys.ini
     * 
     * @var Zend_Config 
     */
    protected $_keys;

    public function init() {
        $this->_keys = Zend_Registry::get('keys');
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

        // this one is for facebook connect
        $code = $this->getRequest()->getParam('code', null);

        // this is for twitter oath
        $oauth_token = $this->getRequest()->getParam('oauth_token', null);


        // do the first query to the openid provider
        if ($openid_identifier && null === $openid_mode) {

            if ('https://www.twitter.com' == $openid_identifier) {
                $adapter = $this->_getTwitterAdapter();
            } else if ('https://www.facebook.com' == $openid_identifier) {
                $adapter = $this->_getFacebookAdapter();
            } else {
                // for openid
                // fetch only email
                $propertiesToRequest = array("email" => true);

                if ('https://www.google.com/accounts/o8/id' == $openid_identifier || 'http://me.yahoo.com/' == $openid_identifier) {
                    $ext = new My_OpenId_Extension_AttributeExchange($propertiesToRequest);
                } else {
                    $ext = new Zend_OpenId_Extension_Sreg($propertiesToRequest);
                }

                $adapter = new Zend_Auth_Adapter_OpenId($openid_identifier);
                $adapter->setExtensions($ext);
            }


            // here a user is redirect to the provider for loging
            $result = $auth->authenticate($adapter);

            // the following two lines should never be executed unless the redirection faild.
            $this->_helper->FlashMessenger('Redirection faild');
            return $this->_redirect('/index/index');
            
        } else if ($openid_mode || $code || $oauth_token) {
            // this will be exectued after provider redirected the user back to us

            if ($code) {
                // for facebook
                $adapter = $this->_getFacebookAdapter();
            } else if ($oauth_token) {
                // for twitter
                $adapter = $this->_getTwitterAdapter();
                $adapter->setQueryData($_GET);
            } else {
                // for openid
                $adapter = new Zend_Auth_Adapter_OpenId();
            }

            $result = $auth->authenticate($adapter);


            var_dump($result->getMessages());
            var_dump($result->getIdentity());
            var_dump($_GET);
           // var_dump($ext->getProperties());
            return;


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

    /**
     * Get My_Auth_Adapter_Facebook adapter
     *
     * @return My_Auth_Adapter_Facebook
     */
    protected function _getFacebookAdapter() {
        extract($this->_keys->facebook->toArray());
        return new My_Auth_Adapter_Facebook($appid, $secret, $redirecturi, $scope);
    }

    /**
     * Get My_Auth_Adapter_Oauth_Twitter adapter
     *
     * @return My_Auth_Adapter_Oauth_Twitter
     */
    protected function _getTwitterAdapter() {
        extract($this->_keys->twitter->toArray());
        return new My_Auth_Adapter_Oauth_Twitter(array(),$appid, $secret, $redirecturi);
    }

}

