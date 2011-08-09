<?php

/**
 * Ja Zend Framework Auth Adapters
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   My/Zend
 * @package    Zend_Auth
 * @subpackage Zend_Auth_Adapter
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: $
 */
/**
 * @see Zend_Auth_Adapter_Interface
 */
require_once APPLICATION_PATH . '/auth/adapter/Oauth.php';

/**
 * I (i.e. Marcin) added verifyCredentials method as well as $_oathConfig variable.
 *
 * @category   Ja/Zend
 * @package    Zend_Auth
 * @subpackage Zend_Auth_Adapter
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class My_Auth_Adapter_Oauth_Twitter extends My_Auth_Adapter_Oauth {

    /**
     * Consumer key, provided by Twitter.com
     *
     * @var string
     */
    protected $_consumerKey = null;
    /**
     * Consumer secret, provided by Twitter.com
     *
     * @var string
     */
    protected $_consumerSecret = null;
    /**
     * URL to redirect the user back from Twitter.com
     *
     * @var string
     */
    protected $_callbackUrl = null;
    /**
     * Array of options for this adapter.  Options include:
     *   - sessionNamespace: session namespace override
     *
     * @var array
     */
    protected $_options = null;

    protected $_oathConfig = array();

    /**
     * Constructor
     *
     * @param array  $options An array of options for this adapter
     * @param string $consumerKey Consumer key
     * @param string $consumerSecret Consumer secret
     * @param string $callbackUrl Callback URL
     */
    public function __construct(array $options = array(), $consumerKey = null, $consumerSecret = null, $callbackUrl = null) {
        $this->setOptions($options);

        if ($consumerKey !== null) {
            $this->setConsumerKey($consumerKey);
        }

        if ($consumerSecret !== null) {
            $this->setConsumerSecret($consumerSecret);
        }

        if ($callbackUrl !== null) {
            $this->setCallbackUrl($callbackUrl);
        }

        $this->_oathConfig = array(
            'callbackUrl' => $this->_callbackUrl,
            'siteUrl' => 'http://twitter.com/oauth',
            'authorizeUrl' => 'https://api.twitter.com/oauth/authenticate',
            'consumerKey' => $this->_consumerKey,
            'consumerSecret' => $this->_consumerSecret,
        );
    }

    /**
     * Sets the consumer key for authentication
     *
     * @param string $consumerKey
     * @return My_Auth_Adapter_Twitter Fluent interface
     */
    public function setConsumerKey($consumerKey) {
        $this->_consumerKey = (string) $consumerKey;
        return $this;
    }

    /**
     * Gets the consumer key
     *
     * @return string|null
     */
    public function getConsumerKey() {
        return $this->_consumerKey;
    }

    /**
     * Sets the consumer secret for authentication
     *
     * @param string $consumerSecret
     * @return My_Auth_Adapter_Twitter Fluent interface
     */
    public function setConsumerSecret($consumerSecret) {
        $this->_consumerSecret = (string) $consumerSecret;
        return $this;
    }

    /**
     * Gets the consumer secret
     *
     * @return string|null
     */
    public function getConsumerSecret() {
        return $this->_consumerSecret;
    }

    /**
     * Sets the callback URL
     *
     * @param string $callbackUrl
     * @return My_Auth_Adapter_Twitter Fluent interface
     */
    public function setCallbackUrl($callbackUrl) {
        $this->_callbackUrl = (string) $callbackUrl;
        return $this;
    }

    /**
     * Gets the callback URL
     *
     * @return string|null
     */
    public function getCallbackUrl() {
        return $this->_callbackUrl;
    }

    /**
     * Authenticate the user
     *
     * @return Zend_Auth_Result
     */
    public function authenticate() {
        if (!$this->_consumerKey) {
            $code = Zend_Auth_Result::FAILURE;
            $message = array('A consumer key is required');
            return new Zend_Auth_Result($code, '', $message);
        }

        if (!$this->_consumerSecret) {
            $code = Zend_Auth_Result::FAILURE;
            $message = array('A consumer secret is required');
            return new Zend_Auth_Result($code, '', $message);
        }

        if (!$this->_callbackUrl) {
            $code = Zend_Auth_Result::FAILURE;
            $message = array('A callback URL is required');
            return new Zend_Auth_Result($code, '', $message);
        }

        require_once 'Zend/Oauth/Consumer.php';
        $consumer = new Zend_Oauth_Consumer($this->_oathConfig);

        $this->setConsumer($consumer);

        return parent::authenticate();
    }

    /**
     * Makes a http://api.twitter.com/version/account/verify_credentials.json
     * request. Returns a representation of the requesting user
     * if authentication was successful
     *
     * @return array representation of the requesting user if authentication was successful
     */
    public function verifyCredentials() {        

        $accessToken = $this->getAccessToken();

        $client = $accessToken->getHttpClient($this->_oathConfig);
        $client->setUri('http://api.twitter.com/1/account/verify_credentials.json');


        $response = $client->request(Zend_Http_Client::GET);

        return json_decode($response->getBody());
    }

}
