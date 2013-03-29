<?php
/**
 * Parse Datasource
 * Allowing interfacing between the Parse API.
 * https://www.parse.com/docs/rest
 *
 * PHP 5
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the below copyright notice.
 *
 * @author     Tom Rothwell <tom@pollenizer.com>
 * @copyright  Copyright 2012, Pollenizer Pty. Ltd. (http://pollenizer.com)
 * @license    MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @since      CakePHP(tm) v 2.0.5
 * 
 */
App::uses('HttpSocket', 'Network/Http');

class ParseSource extends DataSource 
{
    /**
     * var $connection
     */
    public $connection = null;

    /**
     * var $defaultHeader
     */
    public $defaultHeader = array(); 
    /**
     * var $error
     */
    public $error = null;

    /**
     * var $restUrl
     */
    public $restUrl = 'https://api.parse.com/1/';
    
    /**
     * Constructor - Sets the configuration
     */
    public function __construct($config) 
    {
        $this->connection = new HttpSocket();
        $this->defaultHeaders = array(
            'X-Parse-Application-Id' => $config['application_id'],
            'X-Parse-REST-API-Key' => $config['rest_api_key'],
            'Content-Type' => 'application/json;',
        );
        parent::__construct($config);
    }

    /**
     * Allows a user to unset a specific header, not all calls
     * require the default headers (Content-Type), while most do.
     * @param string $type
     * @return boolean
     */
    public function removeHeader($type) 
    {
        if (isset($this->defaultHeaders[$type])) {
            unset($this->defaultHeaders[$type]);
            return true;
        }
        return false;
    }

    /**
     * Sets additional headers
     * @param array $header
     * @return $this->defaultHeaders
     */
    public function setHeader($header) 
    {
        $this->defaultHeaders = array_merge($header, $this->defaultHeaders);
        return $this->defaultHeaders;
    }

    /**
     * Returns an error if set
     * @return $this->error
     */
    public function getError() 
    {
        return $this->error;
    }

    /**
     * Generic function to allow for dynamic calling.
     * @params
     *  string $type - The name within the wsdl to call
     *  mixed $params - Parameters to send to parse
     * @return mixed
     */
    public function query($type = null, $params = array()) 
    {
        //Pre built handling?
        switch ($type) {
        case 'push' :
            return $this->push(array_pop($params));
            break;
        case 'getError' :
            return $this->getError();
            break;
        case 'removeHeader' :
            return $this->removeHeader(array_pop($params));
            break;
        case 'setHeader' :
            return $this->setHeader(array_pop($params));
            break;
        }

        //Otherwise we process the query as normal
        $data = array(
            'uri' => array(
                'scheme' => 'https',
                'host' => 'api.parse.com',
                'path' => '/1/' . $type
            ),
            'header' => $this->defaultHeaders,
        );
        $data = array_merge_recursive($data, array_pop($params));

        return $this->_processQuery($data); 
    }

    /**
     * Holds the default params for a push notification
     * @param array $params
     * @return mixed
     */
    /* $params =
        array(
            'body' => array(
                'channel' => '',
                'type' => 'ios',
                'data' => array(
                )
            )
        ); */
    public function push($params) 
    {
        $data = array(
            'method' => 'POST',
            'uri' => $this->restUrl . 'push',
            'body' => array(),
            'header' => $this->defaultHeaders,
        );

        $data = array_merge($data, $params);
        return $this->_processQuery($data);
    }
    
    /**
     * Handles the api request
     * @param array $data
     * @return mixed
     */
    private function _processQuery($data) 
    {
        //Encrypt the body for the restul API
        if (isset($data['body'])) {
            $data['body'] = json_encode($data['body']);
        }

        try {
            $result = $this->connection->request($data);
            $resultobj = json_decode($result->body, true);

            if ($result->code == 200) {
                return $resultobj;
            } else {
                $this->error = $resultobj['code'] . ' : ' . $resultobj['error'];
                return false;
            }
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }
}