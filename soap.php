<?php
require_once('ServiceListPrinter.php');

$soapClasses 	= array(
	'CVHandler'
);

$slp = new ServiceListPrinter($soapClasses, "http://www.uis.no/cp");

if ($slp->isNonSoapRequest()) {
	//echo "<pre>";var_dump($slp); echo "</pre>";
	$slp->show();
} else {
	$soapClass = $slp->getRequestClass();
	$soapImpl = $soapClass.'Impl';
	include_once($soapImpl.'.php');
	$server = new SoapServer(null, array('uri' => "http://www.uis.no/cp/$soapClass", 'style' => SOAP_DOCUMENT, 'use' => SOAP_LITERAL));
	$server->setClass($soapClass);
	$server->handle();
}

