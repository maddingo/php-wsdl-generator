<?php
require_once('MyExample.php');

class MyExampleImpl implements MyExample {

	public function __construct() {
	}

  function simpleMethod() {
    return "OK";
  }
  
  function paramMethod($param) {
    error_log("method paramMethod called");
  }

  function paramMethod2($param1, $param2) {
    return "paramMethod2 {$param1}, {$param2}";
  }
  
  /**
   * Function returning a complex object, taking one parameter 
   *
   * @param int $id
   * @return object ReturnClass
  */
  function returnObject($id) {
    $result = new ReturnClass();
    $result->intValue = $id;
    $result->stringValue = "_{$id}_";
    return $result;
  }
  
  /**
   * Function throwing a method
   *
   * @throws HandlerError
  */
  function throwMethod() {
    throw new HandlerError("Handler Error", "in method throwMethod");
  }
}
