<?php

class ReturnClass {
  /**
   * @var string
   */
  public $stringValue;
  
  /**
   * @var int
   */
  public $intValue;
  
  /**
   * 
   * @var unknown_type
   */
  public $rest;
}

class HandlerError extends Exception {
  
  /**
   * short message
   * @var string
   */
  public $message;
  
  /**
   * detailed error message
   * @var string
   */
  public $detail;
  
  function __construct($msg, $detail) {
    $this->message = $msg;
    $this->detail = $detail;
  }
}

interface MyExample {

  /**
   * Simple function not throwing any exceptions and just returning a string.
   * @return string
   */	
  function simpleMethod();
  
  /**
   * Function with an integer parameter.
   *
   * @param int $param
   */	
  function paramMethod($param);

  /**
   * Function with one integer and one string parameter, returning a string.
   * 
   * @param string $param1
   * @param int $param2
   * @return string
   */
  function paramMethod2($param1, $param2);
  
  /**
   * Function returning a complex object, taking one parameter 
   *
   * @param int $id
   * @return object ReturnClass
   */
  function returnObject($id);
  
  /**
   * Function throwing a method
   *
   * @throws HandlerError
   */
  function throwMethod();
}

