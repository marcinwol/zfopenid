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
require_once 'Zend/Auth/Adapter/Interface.php';

/**
 * @category   My/Zend
 * @package    Zend_Auth
 * @subpackage Zend_Auth_Adapter
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

class My_Auth_Adapter_Oauth implements Zend_Auth_Adapter_Interface
{
    /**
     * OAuth consumer object
     *
     * @var null|Zend_Oauth_Consumer
     */
    protected $_consumer = null;

    /**
     * OAuth query data
     *
     * @var null|array
     */
    protected $_queryData = null;

    /**
     * OAuth access token after successful authentication
     *
     * @var null|Zend_Oauth_Token_Access
     */
    protected $_accessToken = null;

    /**
     * Array of options for this adapter.  Options include:
     *   - sessionNamespace: session namespace override
     *
     * @var null|array
     */
    protected $_options = null;

    /**
     * Default namespace to store session credentials
     *
     * @var string
     */
    const DEFAULT_SESSION_NAMESPACE = 'My_Auth_Adapter_Oauth';

    /**
     * Constructor
     *
     * @param array  $options An array of options for this adapter
     * @param Zend_Oauth_Consumer $consumer Consumer object
     */
    public function __construct(array $options = array(), Zend_Oauth_Consumer $consumer = null)
    {
        $this->setOptions($options);

        if ($consumer !== null) {
            $this->setConsumer($consumer);
        }
    }

    /**
     * Sets the consumer object for authentication
     *
     * @param Zend_Oauth_Consumer $consumer
     * @return My_Auth_Adapter_Oauth Fluent interface
     */
    public function setConsumer(Zend_Oauth_Consumer $consumer)
    {
        $this->_consumer = $consumer;
        return $this;
    }

    /**
     * Gets the consumer
     *
     * @return Zend_Oauth_Consumer|null
     */
    public function getConsumer()
    {
        return $this->_consumer;
    }

    /**
     * Sets the query data for generation of the access token.  Data
     * is typically passed back to the application from the remote
     * OAuth authentication source.
     *
     * @param array $queryData array of query data
     * @return My_Auth_Adapter_Oauth Fluent interface
     */
    public function setQueryData(array $queryData)
    {
        $this->_queryData = $queryData;
        return $this;
    }

    /**
     * Gets the query data
     *
     * @return array|null
     */
    public function getQueryData()
    {
        return $this->_queryData;
    }

    /**
     * Sets the access token after a successful authentication attempt
     *
     * @param Zend_Oauth_Token_Access $token access token
     * @return My_Auth_Adapter_Oauth Fluent interface
     */
    public function setAccessToken(Zend_Oauth_Token_Access $token)
    {
        $this->_accessToken = $token;
        return $this;
    }

    /**
     * Gets the access token result
     *
     * @return Zend_Oauth_Token_Access|null
     */
    public function getAccessToken()
    {
        return $this->_accessToken;
    }

    /**
     * Returns the array of arrays of options of this adapter.
     *
     * @return array|null
     */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * Sets the array of arrays of options to be used by
     * this adapter.
     *
     * @param  array $options The array of arrays of options
     * @return Provides a fluent interface
     */
    public function setOptions($options)
    {
        $this->_options = is_array($options) ? $options : array();
        return $this;
    }

    /**
     * Authenticate the user
     *
     * @return Zend_Auth_Result
     */
    public function authenticate()
    {
        if (!$this->_consumer) {
            $code = Zend_Auth_Result::FAILURE;
            $message = array('A valid Zend_Oauth_Consumer key is required');
            return new Zend_Auth_Result($code, '', $message);
        }

        $namespace = self::DEFAULT_SESSION_NAMESPACE;

        if (isset($this->_options['sessionNamespace']) && $this->_options['sessionNamespace'] != '') {
            $namespace = $this->_options['sessionNamespace'];
        }

        require_once 'Zend/Session/Namespace.php';
        $session = new Zend_Session_Namespace($namespace);

        try {
            if (!$session->requestToken) {

                $token = $this->_consumer->getRequestToken();

                $session->requestToken = serialize($token);

                $this->_consumer->redirect();

            } else {

                $accessToken = $this->_consumer->getAccessToken($this->_queryData, unserialize($session->requestToken));
              

                $this->setAccessToken($accessToken);

                unset($session->requestToken);

                $body = $accessToken->getResponse()->getBody();              

                $returnParams = array();

                $parts = explode('&', $body);               
                foreach ($parts as $kvpair) {
                    $pair = explode('=', $kvpair);
                    $returnParams[rawurldecode($pair[0])] = rawurldecode($pair[1]);
                }
            }
        } catch (Zend_Oauth_Exception $e) {
            $session->unsetAll();

            $code = Zend_Auth_Result::FAILURE;
            $message = array('Access denied by OAuth source');
            return new Zend_Auth_Result($code, '', $message);
        } catch (Exception $e) {
            $session->unsetAll();

            $code = Zend_Auth_Result::FAILURE;
            $message = array($e->getMessage());
            return new Zend_Auth_Result($code, '', $message);
        }

        return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $returnParams, array());
    }
}