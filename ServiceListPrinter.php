<?php
require_once("WSDL_Gen.php");

class ServiceListPrinter {
	
	private $soapClasses;
	private $reqURL;
	private $reqSelf;
	private $nsBase;
	private $reqClass;
	private $reqClassFile;
	private $wsdlReq;
	private $baseURL;
	
	public function __construct($classes, $nsBase) {
		$this->soapClasses = $classes;
		$this->reqURL = $_SERVER['REQUEST_URI'];
		$this->nsBase = $nsBase;
		$this->reqSelf = $_SERVER["PHP_SELF"];
		$protocol = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
		$this->baseURL= $protocol.$_SERVER["HTTP_HOST"];
		
		$this->reqClass = false;
		$this->reqClassFile = false;
		$this->wsdlReq = false;
		if (isset($_SERVER['PATH_INFO'])) {
			$reqClass = str_replace('/', '', $_SERVER['PATH_INFO']);
			if (in_array($reqClass, $classes)) {
				$this->reqClass = $reqClass;
				$this->reqClassFile = $reqClass.'.php';
				if (isset($_REQUEST['WSDL'])) {
					$this->wsdlReq = true;
				}
			}
		}
	}

	public function isNonSoapRequest() {
		return ($this->reqClass === false || $this->wsdlReq);
	}
	
	public function getRequestClass() {
		return $this->reqClass;
	}
	
	/**
	 * Get Namespace for request class.
	 */
	public function getRequestClassNS() {
		return $this->nsBase.$this->reqClass;
	}
	
	public function show() {
		if ($this->wsdlReq) {
			$this->showWsdl();
		} else {
			$this->showServiceList();
		}
	}
	
	protected function showWsdl() {
		require_once($this->reqClassFile);
		$wsdlgen = new WSDL_Gen($this->reqClass, $this->baseURL.$this->reqSelf, $this->nsBase.$this->reqClass);
		header("Content-Type: text/xml");
		echo $wsdlgen->toXML();
	}
	
	protected function getWsdlUrl($cls) {
		$u = parse_url($this->reqURL);
		
		return $u['path']."/$cls?WSDL&".$u['query'];
	}
	
	protected function showServiceList() {
		echo "<h1>Services</h1>";
		
		foreach ($this->soapClasses as $cls) {
			echo "<h2>$cls</h2>";
			$wsdlUrl = $this->getWsdlUrl($cls); 
			echo "<span class='wsdl-link'>(<a href=\"$wsdlUrl\">WSDL</a>)</span>";
			echo "<h3>Functions</h3>";
	
			require_once("$cls.php");
			// TODO: The endpoint address and namespace are really not necessary at this point
			$gen = new WSDL_Gen($cls, $this->baseURL.$this->reqSelf, $this->nsBase.$cls);
	
			echo "<table>";
			foreach ($gen->operations as $operName => $oper) {
				echo "<tr>";
				echo "<td>";
				
				// return value
				$retMsg = $oper['output'];
				$retString = 'void';
				if (count($retMsg) > 0) {
					$retString =$retMsg[0]['type']; 
				}
				
				// input parameters
				$paramMsg = $oper['input'];
				$paramString = '';
				if (count($paramMsg) > 0) {
					foreach ($paramMsg as $paramEntry) {
						if (strlen($paramString) > 0) {
							$paramString .= ', ';
						}
						$paramString .= "$paramEntry[type] $paramEntry[name]";
					}
				}
				echo "$retString $operName($paramString)";
				echo "</td>";
				
				echo "<td>$oper[documentation]</td>";
				
				echo "</tr>";
			}
			echo "</table>";
			
		}
		echo "<hr/>";
		echo "<p class='impressum'>";
		echo "<script type=\"text/javascript\" src=\"http://www.ohloh.net/p/488478/widgets/project_thin_badge.js\"></script>";
		echo "</p>";
	}
}