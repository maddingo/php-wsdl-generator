<?php

class SoapError extends Exception {
	/**
	 * short error message
	 * @var string
	 */
	public $message;
	
	/**
	 * detailed error message
	 * @var string
	 */
	public $detail; 	
}

/**
 * WSDL_Gen: A WSDL Generator for PHP5
 *
 * This class generates WSDL from a PHP5 class.
 * 
 * Downloaded from http://web.archive.org/web/* /http://www.schlossnagle.org/~george/php/WSDL_Gen.tgz
 */
class WSDL_Gen {
  const SOAP_XML_SCHEMA_VERSION = 'http://www.w3.org/2001/XMLSchema';
  const SOAP_XML_SCHEMA_INSTANCE = 'http://www.w3.org/2001/XMLSchema-instance';
  const SOAP_SCHEMA_ENCODING = 'http://schemas.xmlsoap.org/soap/encoding/';
  const SOAP_ENVELOP = 'http://schemas.xmlsoap.org/soap/envelope/';
  const SCHEMA_SOAP_HTTP = 'http://schemas.xmlsoap.org/soap/http';
  const SCHEMA_SOAP = 'http://schemas.xmlsoap.org/wsdl/soap/';
  const SCHEMA_WSDL = 'http://schemas.xmlsoap.org/wsdl/';
  const SOAP_FAULT = 'SoapError';

  static public $baseTypes = array(
    'int'    => array('ns'   => self::SOAP_XML_SCHEMA_VERSION,
                      'name' => 'int'),
    'float'  => array('ns'   => self::SOAP_XML_SCHEMA_VERSION,
                      'name' => 'float'),
    'string' => array('ns'   => self::SOAP_XML_SCHEMA_VERSION,
                      'name' => 'string'),
    'boolean' => array('ns' => self::SOAP_XML_SCHEMA_VERSION,
                      'name' => 'boolean'),
    'unknown_type' => array('ns' => self::SOAP_XML_SCHEMA_VERSION,
                      'name' => 'anyType')
  );
  
  public $types;
  public $operations = array();
  public $faults = array();
  public $className;
  public $ns;
  public $endpoint;
  public $complexTypes;
  private $mytypes = array();
  
  private $style = SOAP_RPC;
  private $use = SOAP_ENCODED;

  /** The WSDL_Gen constructor
   * @param string $className The class containing the methods to implement
   * @param string $endpoint  The endpoint for the service
   * @param string $ns optional The namespace you want for your service.
   */
  function __construct($className, $endpoint, $ns=false) {
    $this->types = self::$baseTypes;
    $this->className = $className;
    if(!$ns) { $ns = $endpoint; }
    $this->ns = $ns;
    $this->endpoint = $endpoint;
    $this->createPHPTypes();

    $class = new ReflectionClass($className);
    $methods = $class->getMethods();
    $this->discoverOperations($methods);
    $this->discoverTypes();
  }

  protected function discoverOperations($methods) {
    foreach($methods as $method) {
      $this->operations[$method->getName()]['input'] = array();
      $this->operations[$method->getName()]['output'] = array();
      $this->operations[$method->getName()]['fault'] = array();
      $doc = $method->getDocComment();
      
      // extract input params
      if(preg_match_all('|@param\s+(?:object\s+)?(\w+)\s+\$(\w+)|', $doc, $matches, PREG_SET_ORDER)) {
        foreach($matches as $match) {
          $this->mytypes[$match[1]] = 1;
          $this->operations[$method->getName()]['input'][] = 
                array('name' => $match[2], 'type' => $match[1]);
        }
      }
      
      // extract return types
      if(preg_match('|@return\s+(?:object\s+)?(\w+)|', $doc, $match)) {
      	$this->mytypes[$match[1]] = 1;
        $this->operations[$method->getName()]['output'][] = 
              array('name' => 'return', 'type' => $match[1]);
      }
      
      // extract exception
      if(preg_match('|@throws\s+(?:object\s+)?(\w+)|', $doc, $match)) {
      	$this->mytypes[$match[1]] = 1;
        $this->operations[$method->getName()]['fault'][] = $match[1];
        $this->faults[] = $match[1];
      }
            
      // extract documentation
      $comment = trim($doc);
      $commentStart = strpos($comment, '/**') + 3;
      $comment = trim(substr($comment, $commentStart, strlen($comment)-5));
      $description = '';
      $lines = preg_split("(\\n\\r|\\r\\n\\|\\r|\\n)", $comment);
      foreach ($lines as $line) {
        $line = trim($line);
        $lineStart = strpos($line, '*');
        if ($lineStart === false) {
        	$lineStart = -1;
        }
        $line = trim(substr($line, $lineStart + 1));
        if (!isset($line[0]) || $line[0] != "@") {
        	if (strlen($line) > 0) {
        		$description .= "\n$line";
        	}
        }
      }
	  $this->operations[$method->getName()]['documentation'] = $description;
    }
  }
  
  protected function discoverTypes() {
    foreach(array_keys($this->mytypes) as $type) {
    	if(!isset($this->types[$type])) {
        $this->addComplexType($type);
      }
    }
  }
  
  protected function createPHPTypes() {
    $this->complexTypes['mixed'] = array(
                                     array('name' => 'varString',
                                           'type' => 'string'),
                                     array('name' => 'varInt',
                                           'type' => 'int'),
                                     array('name' => 'varFloat',
                                           'type' => 'float'),
                                     array('name' => 'varArray',
                                           'type' => 'array'),
                                     array('name' => 'varBoolean',
                                           'type' => 'boolean')
                                     );
    $this->types['mixed'] = array('name' => 'mixed', 'ns' => $this->ns);
    $this->types['array'] = array('name' => 'array', 'ns' => $this->ns);
  }
  
  protected function addComplexType($className) {
    $class = new ReflectionClass($className);
    $this->complexTypes[$className] = array();
    $this->types[$className] = array('name' => $className, 'ns' => $this->ns);

    foreach($class->getProperties() as $prop) {
      $doc = $prop->getDocComment();
      if(preg_match('|@var\s+(?:object\s+)?(\w+)|', $doc, $match)) {
      	$type = $match[1];
        $this->complexTypes[$className][] = array('name' => $prop->getName(), 'type' => $type);
        if(!isset($this->types[$type])) {
          $this->addComplexType($type);
        }
      }
    }
  }
  
  protected function addMessages(DomDocument $doc, DomElement $root) {
    $nsPrefix = $root->lookupPrefix($this->ns);
    foreach($this->operations as $name => $params) {
      $msgConfig = array(
      	'input' => array('postfix' => '', 'partName' => 'partParams'),
      	'output' => array('postfix' => 'Response', 'partName' => 'partResponse')
      );
      

	  foreach($msgConfig as $type => $cfg) {
        $el = $doc->createElementNS(self::SCHEMA_WSDL, 'message');
        $fullName = "$name".ucfirst($cfg['postfix']);
        $el->setAttribute("name", $fullName);
        $part = $doc->createElementNS(self::SCHEMA_WSDL, 'part');
        
        $part->setAttribute('element', "$nsPrefix:$fullName");
        $part->setAttribute('name', $cfg['partName']);
        $el->appendChild($part);
        $root->appendChild($el);
      }
    }
      
    // fault messages
    foreach ($this->faults as $faultType) {
    $el = $doc->createElementNS(self::SCHEMA_WSDL, 'message');
      $el->setAttribute("name", $faultType);
    $part = $doc->createElementNS(self::SCHEMA_WSDL, 'part');
      $part->setAttribute('element', "$nsPrefix:$faultType");
      $part->setAttribute('name', 'partException');
    $el->appendChild($part);
    $root->appendChild($el);
  }
  }
  
  protected function addPortType(DomDocument $doc, DomElement $root) {
    $el = $doc->createElementNS(self::SCHEMA_WSDL, 'portType');
    $el->setAttribute('name', $this->className."PortType");
    $nsPrefix = $root->lookupPrefix($this->ns);
    foreach($this->operations as $name => $params) {
      $op = $doc->createElementNS(self::SCHEMA_WSDL, 'operation');
      $op->setAttribute('name', $name);
      $opDocu = $doc->createElementNS(self::SCHEMA_WSDL, 'documentation');
      $docuText = $doc->createTextNode($params['documentation']);
      $opDocu->appendChild($docuText);
      $op->appendChild($opDocu);
      foreach(array('input' => '', 'output' => 'Response') as $type => $postfix) {
        $sel = $doc->createElementNS(self::SCHEMA_WSDL, $type);
        $fullName = "$name".ucfirst($postfix);
        $sel->setAttribute('message', "$nsPrefix:$fullName");
        $sel->setAttribute('name', $fullName);
        $op->appendChild($sel);
      }
      foreach($params['fault'] as $faultType) {
      $sel = $doc->createElementNS(self::SCHEMA_WSDL, 'fault');
      	$sel->setAttribute('message', "$nsPrefix:$faultType");
      	$sel->setAttribute('name', $faultType.'Fault');
      $op->appendChild($sel); 
      }
      $el->appendChild($op);
    }
    $root->appendChild($el);
  }
  
  protected function addBinding(DomDocument $doc, DomElement $root) {
  	$nsPrefix = $root->lookupPrefix($this->ns);
    $el = $doc->createElementNS(self::SCHEMA_WSDL, 'binding');
    $el->setAttribute('name', $this->className."Binding");
    $el->setAttribute('type', "$nsPrefix:{$this->className}PortType");

    $s_binding = $doc->createElementNS(self::SCHEMA_SOAP, 'binding');
    $s_binding->setAttribute('style', 'document');
    $s_binding->setAttribute('transport', self::SCHEMA_SOAP_HTTP);
    $el->appendChild($s_binding);

    foreach($this->operations as $name => $params) {
      $op = $doc->createElementNS(self::SCHEMA_WSDL, 'operation');
      $op->setAttribute('name', $name);
      foreach(array('input', 'output') as $type) {
        $sel = $doc->createElementNS(self::SCHEMA_WSDL, $type);
        $s_body = $doc->createElementNS(self::SCHEMA_SOAP, 'body');
        $s_body->setAttribute('use', 'literal');
        $sel->appendChild($s_body);
        $op->appendChild($sel);
      }
      foreach ($params['fault'] as $faultType) {
      	$sel = $doc->createElementNS(self::SCHEMA_WSDL, 'fault');
      	$sel->setAttribute('name', $faultType.'Fault');
        $s_body = $doc->createElementNS(self::SCHEMA_SOAP, 'body');
        $s_body->setAttribute('use', 'literal');
        $sel->appendChild($s_body);
        $op->appendChild($sel);
      } 
      $el->appendChild($op);
    }
    $root->appendChild($el);
  }
  
  protected function addService(DomDocument $doc, DomElement $root) {
  	$nsPrefix = $root->lookupPrefix($this->ns);
    $el = $doc->createElementNS(self::SCHEMA_WSDL, 'service');
    $el->setAttribute('name', "{$this->className}Service");

    $port = $doc->createElementNS(self::SCHEMA_WSDL, 'port');
    $port->setAttribute('name', "{$this->className}Port");
    $port->setAttribute('binding', "$nsPrefix:{$this->className}Binding");

    $addr = $doc->createElementNS(self::SCHEMA_SOAP, 'address');
    $addr->setAttribute('location', $this->endpoint);

    $port->appendChild($addr);
    $el->appendChild($port);
    $root->appendChild($el);
  }
  
  protected function addTypes(DomDocument $doc, DomElement $root) {
    $types = $doc->createElementNS(self::SCHEMA_WSDL, 'types');
    $root->appendChild($types);
    $el = $doc->createElementNS(self::SOAP_XML_SCHEMA_VERSION, 'schema');
    $el->setAttribute('attributeFormDefault', 'qualified');
    $el->setAttribute('elementFormDefault', 'qualified');
    $el->setAttribute('targetNamespace', $this->ns);
    $types->appendChild($el);

    foreach($this->complexTypes as $name => $data) {
      if ($name == 'mixed') {
      	continue;
      }
      $ct = $doc->createElementNS(self::SOAP_XML_SCHEMA_VERSION, 'complexType');
      $ct->setAttribute('name', $name);

      $all = $doc->createElementNS(self::SOAP_XML_SCHEMA_VERSION, 'sequence');

      foreach($data as $prop) {
        $p = $doc->createElementNS(self::SOAP_XML_SCHEMA_VERSION, 'element');
        $p->setAttribute('name', $prop['name']);
        $prefix = $root->lookupPrefix($this->types[$prop['type']]['ns']);
        $p->setAttribute('type', "$prefix:".$this->types[$prop['type']]['name']);
        $all->appendChild($p);
      }
      $ct->appendChild($all);
      $el->appendChild($ct);
    }
    
    $nsPrefix = $root->lookupPrefix($this->ns);
    
    // Add message types
	foreach($this->operations as $name => $params) {
      foreach(array('input' => '', 'output' => 'Response') as $type => $postfix) {
      	$ce = $doc->createElementNS(self::SOAP_XML_SCHEMA_VERSION, 'element');
        $fullName = "$name".ucfirst($postfix);
      	$ce->setAttribute('name', $fullName);
	    $ce->setAttribute('type', "$nsPrefix:$fullName");
      	$el->appendChild($ce);
        
      	$ct = $doc->createElementNS(self::SOAP_XML_SCHEMA_VERSION, 'complexType');
        $ct->setAttribute('name', $fullName);
        $ctseq = $doc->createElementNS(self::SOAP_XML_SCHEMA_VERSION, 'sequence');
        $ct->appendChild($ctseq); 
        foreach($params[$type] as $param) {
          $pare = $doc->createElementNS(self::SOAP_XML_SCHEMA_VERSION, 'element');
          $pare->setAttribute('name', $param['name']);
          $prefix = $root->lookupPrefix($this->types[$param['type']]['ns']);
          $pare->setAttribute('type', "$prefix:".$this->types[$param['type']]['name']);
          $ctseq->appendChild($pare);
        }
        $el->appendChild($ct);
      }
    }
    
    // Add fault elements
    foreach ($this->faults as $faultType) {
      $ce = $doc->createElementNS(self::SOAP_XML_SCHEMA_VERSION, 'element');
      $ce->setAttribute('name', $faultType);
      $prefix = $root->lookupPrefix($this->types[$faultType]['ns']);
      $ce->setAttribute('type', $prefix.':'.$faultType);
      $el->appendChild($ce);
      
    $ce = $doc->createElementNS(self::SOAP_XML_SCHEMA_VERSION, 'element');
      $ce->setAttribute('name', $faultType.'Fault');
      $ct = $doc->createElementNS(self::SOAP_XML_SCHEMA_VERSION, 'complexType');
      $seq = $doc->createElementNS(self::SOAP_XML_SCHEMA_VERSION, 'sequence');
      $elFault = $doc->createElementNS(self::SOAP_XML_SCHEMA_VERSION, 'element');
      $elFault->setAttribute('ref', $prefix.':'.$faultType);
      $seq->appendChild($elFault);
      $ct->appendChild($seq);
      $ce->appendChild($ct);
    $el->appendChild($ce);
  }
  }

  /**
   * Return an XML representation of the WSDL file
   */
  public function toXML() {
    $wsdl = new DomDocument("1.0");
    //$root = $wsdl->createElementNS('http://schemas.xmlsoap.org/wsdl/', 'definitions');
    $root = $wsdl->createElement('wsdl:definitions');
    $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsd','http://www.w3.org/2001/XMLSchema');
    $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:tns', $this->ns);
    $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:soap-env',self::SCHEMA_SOAP);
    $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:wsdl',self::SCHEMA_WSDL);
    $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:soapenc',self::SOAP_SCHEMA_ENCODING);
    $root->setAttribute('targetNamespace', $this->ns);
    $this->addTypes($wsdl, $root);
    $this->addMessages($wsdl, $root);
    $this->addPortType($wsdl, $root);
    $this->addBinding($wsdl, $root);
    $this->addService($wsdl, $root);

    $wsdl->appendChild($root);
    return $wsdl->saveXML();
  }
}

/* vim: set ts=2 sts=2 bs=2 ai expandtab : */

