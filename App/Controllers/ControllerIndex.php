<?php

namespace App\Controllers; //declarando o namespace

use App\Views\Cabecalho;

class ControllerIndex{

	private $cab;

	function __construct(){ // construtor da classe
		$this->cab = new Cabecalho();
	}

	// pagina inicial
	public function index(){
		$this->cab->abertura("Animaling - Entre ou cadastre-se");
		include_once "../App/Views/login.php";
		$this->cab->fechamento();
	}

	public function logon($pPost){
		echo $pPost['formLogin'];
		echo $pPost['formSenha'];
		session_start();
		$_SESSION['login'] = $pPost['formLogin'];
		header("location: localhost:8080/animaling3/public/".$_SESSION['login']);

	}


}

?>