<?php

class ReturnClass {
	/**
	 * 
	 * @var string
	 */
	public $name;
	
	/**
	 * 
	 * @var int
	 */
	public $counter;
	
	/**
	 * 
	 * @var unknown_type
	 */
	public $rest;
}

interface CVHandler {

	/**
	 * Full synchronize beetween IDM and CP, updates person info for existing articles, 
	 * deletes the one not existing longer in IDM and create new ones that are not in CP 
	 *
	 * @param int $id 
	 * @return string
	 */	
	function synchronize($id=null);
	
	/**
	 * Generates JSON file for competence areas from DATABASE (UiSAdm01)
	 * @return string
	 */	
	function generateJsonCompetenceAreas();
	
    /**
	 * Generates JSON file for employees, from  CP articles 
	 * @return string
	 */	
    function generateJsonEmployees();

    /**
     * Delete JSON cached departments
     * @return string
     */
    function deleteJsonDepartments();
	
	/**
	 * Update CP article for a Person (ID) 
	 *
	 * @param int $id	 
	 * @return object ReturnClass
	 */
	function updateByID($id);
}
