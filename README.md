php-wsdl-generator
==================
PHP has SOAP support. What is missing is a WSDL generator that supports document/literal SOAP style.

The original code was published on http://www.schlossnagle.org/~george/php/WSDL_Gen.tgz and supported only RPC encoded SOAP. I put a copy of the original under Downloads.

Unfortunately the site is not operational any longer and there is no license attached to the package. If you are the original developer of the package, please step forward, I would like to give you some credit.

NB: complex types as return types are not supported because PHP's SoapServer class does not create a proper return. For now, just return a string. You can json-encode complex structures. 