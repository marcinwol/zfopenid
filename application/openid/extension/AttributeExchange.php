<?php
/**
 * Extension class for Zend_OpenId.  Provides attribute exchange protocol.
 *
 * @author Chris Bisnett cbisnett@gmail.com
 * @date 19July2009
 */
class My_OpenId_Extension_AttributeExchange extends Zend_OpenId_Extension
{
	/**
	 * Namespace URI for attribute exchange version 1.0
	 */
	const NS_URL = 'http://openid.net/srv/ax/1.0';

	/**
	 * Defined attributes listed at http://www.axschema.org/types as of 19July2009.
	 */
	private $_definedAttributes = array(
		// Name types
		'userName' => 'http://axschema.org/namePerson/friendly',
		'fullName' => 'http://axschema.org/namePerson',
		'namePrefix' => 'http://axschema.org/namePerson/prefix',
		'firstName' => 'http://axschema.org/namePerson/first',
		'lastName' => 'http://axschema.org/namePerson/last',
		'middleName' => 'http://axschema.org/namePerson/middle',
		'nameSuffix' => 'http://axschema.org/namePerson/suffix',

		// Work types
		'company' => 'http://axschema.org/company/name',
		'jobTitle' => 'http://axschema.org/company/title',

		// Date of birth
		'birthDate' => 'http://axschema.org/birthDate',
		'birthYear' => 'http://axschema.org/birthDate/birthYear',
		'birthMonth' => 'http://axschema.org/birthDate/birthMonth',
		'birthDay' => 'http://axschema.org/birthDate/birthday',

		// Telephone
		'phoneDefault' => 'http://axschema.org/contact/phone/default',
		'phoneHome' => 'http://axschema.org/contact/phone/home',
		'phoneBusiness' => 'http://axschema.org/contact/phone/business',
		'phoneCell' => 'http://axschema.org/contact/phone/cell',
		'phoneFax' => 'http://axschema.org/contact/phone/fax',

		// Address
		'address' => 'http://axschema.org/contact/postalAddress/home',
		'address2' => 'http://axschema.org/contact/postalAddressAdditional/home',
		'city' => 'http://axschema.org/contact/city/home',
		'state' => 'http://axschema.org/contact/state/home',
		'country' => 'http://axschema.org/contact/country/home',
		'postalCode' => 'http://axschema.org/contact/postalCode/home',

		// Email
		'email' => 'http://axschema.org/contact/email',

		// Instant messaging
		'aim' => 'http://axschema.org/contact/IM/AIM',
		'icq' => 'http://axschema.org/contact/IM/ICQ',
		'msn' => 'http://axschema.org/contact/IM/MSN',
		'yahoo' => 'http://axschema.org/contact/IM/Yahoo',
		'jabber' => 'http://axschema.org/contact/IM/Jabber',
		'skype' => 'http://axschema.org/contact/IM/Skype',

		// Web sites
		'webPage' => 'http://axschema.org/contact/web/default',
		'blog' => 'http://axschema.org/contact/web/blog',
		'linkedIn' => 'http://axschema.org/contact/web/Linkedin',
		'amazon' => 'http://axschema.org/contact/web/Amazon',
		'flickr' => 'http://axschema.org/contact/web/Flickr',
		'delicious' => 'http://axschema.org/contact/web/Delicious',

		// Audio/Video greetings
		'spokenName' => 'http://axschema.org/media/spokenname',
		'audioGreeting' => 'http://axschema.org/media/greeting/audio',
		'videoGreeting' => 'http://axschema.org/media/greeting/video',

		// Images
		'defaultImage' => 'http://axschema.org/media/image/default',
		'squareImage' => 'http://axschema.org/media/image/aspect11',
		'43Image' => 'http://axschema.org/media/image/aspect43',
		'34Image' => 'http://axschema.org/media/image/aspect34',
		'favIcon' => 'http://axschema.org/media/image/favicon',

		// Misc details
		'gender' => 'http://axschema.org/person/gender',
		'language' => 'http://axschema.org/pref/language',
		'timezone' => 'http://axschema.org/pref/timezone',
		'biography' => 'http://axschema.org/media/biography'
	);

	/**
	 * Name/Value pairs of requested attributes and a boolean specifying if the attribute is required.
	 */
	private $_attributes = array();

	/**
	 * Holds the attribute and the returned value.
	 */
	private $_properties = array();

	public function __construct(array $attribs)
	{
		$this->_attributes = $attribs;
	}

	/**
	 * Gets the property values returned by the provider.
	 *
	 * @return array
	 */
	public function getProperties()
	{
		return $this->_properties;
	}

	private function splitParams($params)
	{
		$final = array();        

		// Loop the parameters
		foreach ($params as $identifier => $value)
		{
			// Split the identifier at the boundries
			$levels = explode('_', $identifier);

			// Get the last value as the key
			$key = array_pop($levels);

			// Loop the levels, creating any that don't exist
			$currentlevel = &$final;
			foreach ($levels as $level)
			{
				// Check if the level is defined
				if (!isset($currentlevel[$level]))
					// Create the level
					$currentlevel[$level] = array();
				else
				{
					// Change any found value to the first key of an array
					if (!is_array($currentlevel[$level]))
						$currentlevel[$level] = array($currentlevel[$level]);
				}

				// Move down to the next level
				$currentlevel = &$currentlevel[$level];
			}

			// Set the value
			$currentlevel[$key] = $value;
		}

		return $final;
	}

	/**
	 * Adds an attribute type and URI to the list of defined attributes.  This
	 * can be used to add expiremental types for testing.
	 *
	 * @param array &$attribs New attribute/URI pairs
	 * @return bool
	 */
	public function addType($attribs)
	{
		// Verify the attributes is an array
		if (!is_array($attribs))
			return false;

		// Merge the new types into the defined attributes
		$this->_definedAttributes = array_merge($this->_definedAttributes, $attribs);

		return true;
	}

    /**
     * Generates a request to be sent to the provider requesting the
     * specified attributes.
     *
     * @param array &$params request's var/val pairs
     * @return bool
     */
    public function prepareRequest(&$params)
    {
    	// Don't add attributes if there are none
    	if (!is_array($this->_attributes) || count($this->_attributes) < 1)
    		return;

    	// Setup the lists
    	$requiredAttributes = '';
    	$availableAttributes = '';

    	// Set the name space
    	$params['openid.ns.ax'] = My_OpenId_Extension_AttributeExchange::NS_URL;

    	// Set the mode
    	$params['openid.ax.mode'] = 'fetch_request';

    	// Loop the attributes only adding those that are valid
    	foreach ($this->_attributes as $attr => $isRequired)
    	{
    		// Check if the attribute is defined
    		if (!isset($this->_definedAttributes[$attr]))
    			continue;

    		// Add the attribute to a list
    		if ($isRequired)
    			$requiredAttributes .= (empty($requiredAttributes)) ? $attr : ',' . $attr;
    		else
    			$availableAttributes .= (empty($availableAttributes)) ? $attr : ',' . $attr;

    		// Add the type
    		$params['openid.ax.type.' . $attr] = $this->_definedAttributes[$attr];
    	}

    	// Add the required
    	if (!empty($requiredAttributes))
    		$params['openid.ax.required'] = $requiredAttributes;

    	// Add the requested
    	if (!empty($availableAttributes))
    		$params['openid.ax.if_available'] = $availableAttributes;

        return true;
    }

    /**
     * Parses the request from the consumer to determine what attribute values
     * to return to the consumer.
     *
     * @param array $params request's var/val pairs
     * @return bool
     */
    public function parseRequest($params)
    {
        return true;
    }

    /**
     * Generates a response to the consumer's request that contains the
     * requested attributes.
     *
     * @param array &$params response's var/val pairs
     * @return bool
     */
    public function prepareResponse(&$params)
    {
        return true;
    }

    /**
     * Gets property values from the response returned by the provider
     *
     * @param array $params response's var/val pairs
     * @return bool
     */
    public function parseResponse($params)
    {
    	$params = $this->splitParams($params);
          
    	$ax = null;

    	// Get the data name space
    	if (isset($params['openid']['ns']['ax']) && $params['openid']['ns']['ax'] == My_OpenId_Extension_AttributeExchange::NS_URL)
    		$ax = $params['openid']['ax'];
    	else
    	{
    		// Loop the extensions looking for the namespace url
    		foreach ($params['openid']['ns'] as $namespace => $uri)
    		{
    			// Check if the uri is attribute exchange
    			if ($uri == My_OpenId_Extension_AttributeExchange::NS_URL)
    			{
    				$ax = $params['openid'][$namespace];
    				break;
    			}
    		}
    	}

             

    	// Check if the data was found
    	if ($ax == null)
    		return false;

    	// Verify the mode is fetch_response
    	if (isset($ax['mode']) && $ax['mode'] != 'fetch_response')
    		return false;

       

    	// Get the attributes
    	foreach ($ax['value'] as $attr => $value)
    	{
    		$this->_properties[$attr] = $value;
    	}

        return true;
    }
}