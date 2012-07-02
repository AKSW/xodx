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
    public function getType ()
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
}
