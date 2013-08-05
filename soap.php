<?php
require_once('ServiceListPrinter.php');

$soapClasses 	= array(
	'MyExample'
);

$nsprefix = 'http://github.com/maddingo/';

$slp = new ServiceListPrinter($soapClasses, $nsprefix);

if ($slp->isNonSoapRequest()) {
	$slp->show();
} else {
	try {
	  error_log('handling request');
		$soapClass = $slp->getRequestClass();
		error_log("SOAP CLASS: $soapClass");
		$soapImpl = $soapClass.'Impl';
		require_once($soapImpl.'.php');
		$server = new SoapServer(null, array('uri' => "{$nsprefix}{$soapClass}", 'style' => SOAP_DOCUMENT, 'use' => SOAP_LITERAL));
		$server->setClass($soapImpl);
		$server->handle();
	} catch(Exception $ex) {
		 $slp->fault($ex);
	}
}

