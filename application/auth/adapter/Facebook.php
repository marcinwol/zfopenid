<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Facebook
 *
 * @link http://www.krotscheck.net/2010/08/21/zend_auth_adapter_facebook.html
 * @author Michael Krotscheck
 */
class My_Auth_Adapter_Facebook implements Zend_Auth_Adapter_Interface
{
    /**
     * The Authentication URI, used to bounce the user to the facebook redirect uri.
     *
     * @var string
     */
    const AUTH_URI = 'https://graph.facebook.com/oauth/authorize?client_id=%s&redirect_uri=%s';

    /**
     * The token URI, used to retrieve the OAuth Token.
     *
     * @var string
     */
    const TOKEN_URI = 'https://graph.facebook.com/oauth/access_token';


    /**
     * The user URI, used to retrieve information about the user.
     *
     * @var string
     */
    const USER_URI = 'https://graph.facebook.com/me';

    /**
     * The application ID
     *
     * @var string
     */
    private $_appId = null;

    /**
     * The application secret
     *
     * @var string
     */
    private $_secret = null;

    /**
     * The authentication scope (advanced options) requested
     *
     * @var string
     */
    private $_scope = null;

    /**
     * The redirect uri
     *
     * @var string
     */
    private $_redirectUri = null;

    /**
     * Constructor
     *
     * @param string $appId the application ID
     * @param string $secret the application secret
     * @param string $scope the application scope
     * @param string $redirectUri the URI to redirect the user to after successful authentication
     */
    public function __construct($appId, $secret, $redirectUri, $scope)
    {
        $this->_appId = $appId;
        $this->_secret = $secret;
        $this->_scope = $scope;
        $this->_redirectUri   = $redirectUri;
    }

    /**
     * Sets the value to be used as the application ID
     *
     * @param  string $appId The application ID
     * @return My_Auth_Adapter_Facebook Provides a fluent interface
     */
    public function setAppId($appId)
    {
        $this->_appId = $id;
        return $this;
    }

    /**
     * Sets the value to be used as the application secret
     *
     * @param  string $secret The application secret
     * @return My_Auth_Adapter_Facebook Provides a fluent interface
     */
    public function setSecret($secret)
    {
        $this->_secret = $secret;
        return $this;
    }

    /**
     * Sets the value to be used as the application scope (array())
     *
     * @param  string $scope The application scope
     * @return My_Auth_Adapter_Facebook Provides a fluent interface
     */
    public function setApplicationScope($scope)
    {
        $this->_scope = $scope;
        return $this;
    }

    /**
     * Sets the redirect uri after successful authentication
     *
     * @param  string $redirectUri The redirect URI
     * @return My_Auth_Adapter_Facebook Provides a fluent interface
     */
    public function setRedirectUri($redirectUri)
    {
        $this->_redirectUri = $redirectUri;
        return $this;
    }


    /**
     * Authenticates the user against facebook
     * Defined by Zend_Auth_Adapter_Interface.
     *
     * @throws Zend_Auth_Adapter_Exception If answering the authentication query is impossible
     * @return Zend_Auth_Result
     */
    public function authenticate()
    {
    	// Get the request object.
    	$frontController = Zend_Controller_Front::getInstance();
    	$request = $frontController->getRequest();

    	// First check to see wether we're processing a redirect response.
    	$code = $request->getParam('code');

    	if ( empty ($code ) )
    	{
	    	// Create the initial redirect
	    	$loginUri = sprintf(My_Auth_Adapter_Facebook::AUTH_URI , $this->_appId, $this->_redirectUri);

	    	if ( !empty($this->_scope) )
	    	{
	    		$loginUri .= "&scope=" . $this->_scope;
	    	}

	    	header('Location: ' . $loginUri );
    	}
    	else
    	{
    		// Looks like we have a code. Let's get ourselves an access token
	    	$client = new Zend_Http_Client( My_Auth_Adapter_Facebook::TOKEN_URI );
	    	$client->setParameterGet('client_id', $this->_appId);
	    	$client->setParameterGet('client_secret', $this->_secret);
	    	$client->setParameterGet('code', $code);
	    	$client->setParameterGet('redirect_uri', $this->_redirectUri);

	    	$result = $client->request('GET');
	    	$params = array();
	    	parse_str($result->getBody(), $params);

	    	// REtrieve the user info
	    	$client = new Zend_Http_Client(My_Auth_Adapter_Facebook::USER_URI );
	    	$client->setParameterGet('client_id', $this->_appId);
	    	$client->setParameterGet('access_token', $params['access_token']);
	    	$result = $client->request('GET');
	    	$user = json_decode($result->getBody());

            return new Zend_Auth_Result( Zend_Auth_Result::SUCCESS, $user->id, array('user'=>$user, 'token'=>$params['access_token']) );
    	}

        return new Zend_Auth_Result( Zend_Auth_Result::FAILURE, null, 'Error while attempting to redirect.' );
    }
}
?>
