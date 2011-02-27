<?php

class IndexController extends Zend_Controller_Action {

    public function init() {
        /* Initialize action controller here */
    }

    public function indexAction() {

        //   var_dump(Zend_Registry::get('test'));
//           var_dump($this->getInvokeArg('bootstrap')->getResource('test'));
//            var_dump($this->getInvokeArg('bootstrap')->getContainer()->test);
//
//        return;

        $auth = Zend_Auth::getInstance();

        if ($auth->hasIdentity()) {
            $this->view->identity = $auth->getIdentity();

            if (isset($this->view->identity['properties']['email'])) {
                $this->view->email = $this->view->identity['properties']['email'];
            } else {
                $this->view->email = 'some@empty.email';
            }
            
        } else {
            $this->view->identity = null;
        }
    }

}

