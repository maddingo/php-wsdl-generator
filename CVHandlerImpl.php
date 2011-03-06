<?php

class CVHandlerImpl implements CVHandler {

	public function __construct() {
	}
	
	public function synchronize($id = null){	
		return "OK";
	}

	public function generateJsonCompetenceAreas() {
		return "OK";	
	}
	
	
	public function generateJsonEmployees(){
		return "OK";
	}
	
	public function deleteJsonDepartments(){
		return "Files deleted";
	}
	
	public function updateByID($id) {
		return $this->synchronize($id); 
	}

	private function deleteArticleByUserID($id){
		return true;
	}	
}
