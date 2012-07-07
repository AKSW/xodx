<?php

class Xodx_Request
{
    /**
     * The request method. GET, POST, SESSION, ...
     */
    private $_method;

    /**
     * Array keeping the values of the request
     * The types in the first dimension, the keys in the second dimension and the values as values
     * An additional 1st dimension key 'all' holds the key-type mappings
     */
    private $_values;

    /**
     * Constructor for the Request object.
     * Takes the method of the request and an array of the values.
     * @param $method
     * @param $values Array with the method, keys and values (see private member $_values)
     */
    public function __construct ($method, array $values)
    {
        $this->_method = strtolower($method);
        $this->_values = $values;
    }

    /**
     * Returns the method of the request (e.g. GET, POST, ...)
     */
    public function getMethod ()
    {
        return $this->_method;
    }

    /**
     * Returns the value for the given key.
     * @param $key The key of the value which should be returned
     * @param $method optional, if this parameter is specified only values transfered with this method
     *              are taken into account. If this parameter is empty the priority is get, post,
     *              session (the last overwerites the first).
     */
    public function hasValue ($key, $method = null)
    {
        $key = $this->_replaceKey($key);
        if ($method === null) {
            if (isset($this->_values['all'][$key])) {
                $method = $this->_values['all'][$key];
            } else {
                return false;
            }
        }

        return isset($this->_values[strtolower($method)][$key]);
    }

    /**
     * Returns the value for the given key.
     * @param $key The key of the value which should be returned
     * @param $method optional, if this parameter is specified only values transfered with this method
     *              are taken into account. If this parameter is empty the priority is get, post,
     *              session (the last overwerites the first).
     */
    public function getValue ($key, $method = null)
    {
        $key = $this->_replaceKey($key);
        if ($method === null) {
            if (isset($this->_values['all'][$key])) {
                $method = $this->_values['all'][$key];
            } else {
                return null;
            }
        }

        if (isset($this->_values[strtolower($method)][$key])) {
            return $this->_values[strtolower($method)][$key];
        } else {
            return null;
        }
    }

    /**
     * Set the raw body of the current request.
     * @param $body This should be the result of file_get_contents('php://input')
     */
    public function setBody ($body)
    {
        $this->_values['body'] = $body;
    }

    /**
     * Tells whether there was a body submitted or not.
     * @return boolean true if there is a body, else false
     */
    public function hasBody ()
    {
        return isset($this->_values['body']) && ($this->_values['body'] !== null);
    }

    /**
     * Gives back the body which was submitted with this request
     * @return string The raw body of the request
     */
    public function getBody ()
    {
        if (isset($this->_values['body'])) {
            return $this->_values['body'];
        } else {
            return null;
        }
    }

    /**
     * As described in [1] and [2] PHP replaces dots and other chars with an underscore. To get the
     * right keys from the array this method replaces them too.
     * [1] http://ca.php.net/variables.external
     * [2] http://stackoverflow.com/questions/68651/
     *     can-i-get-php-to-stop-replacing-characters-in-get-or-post-arrays
     */
    private function _replaceKey ($key)
    {
        $chars = array(' ', '.', '[');
        for ($i = 128; $i <= 159; $i++ ) {
            $chars[] = chr($i);
        }
        return str_replace($chars, '_', $key);
    }
}
