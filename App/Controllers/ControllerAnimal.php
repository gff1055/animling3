<?php
namespace App\Controllers;

use App\Models\ModelAnimal;
use App\Models\Animal;
use App\Models\ModelStatus;
use App\Models\ModelInteracao;
use App\Models\Interacao;
use App\Models\Status;
use App\Views\Cabecalho;
use App\Init;

class ControllerAnimal{

	public function index($nick){

		session_start();
		$cab = new Cabecalho($nick);

		// carregando os dados do usuario
		$modelAnimal = new ModelAnimal(Init::getDB());
		$dadosAnimal = $modelAnimal->exibirDadosAnimal($nick);

		$modelInteracao = new ModelInteracao(Init::getDB());	// declaracao de variaval para acesso a tabela de Interacoes(seguidores seguidos)

		$count = $this->countInteractions($dadosAnimal['codigo']);	// variavel recebe array que contem a quantidade de seguidores e seguidos de um usuario
		
		$modelStatus = new ModelStatus(Init::getDB());	// declarando variavel para acesso a tabela de Status no banco


		$acessoNaoLogado = false;	// flag que indica se o acesso a página é de alguem nao logado
		$acessoUsuarioSessao = null;	// flag que indica se o usuario da sessão esta acessando seu próprio perfil

		/*Testando se o acesso é de alguem logado*/
		if(isset($_SESSION['login'])) {
			/*echo $_SESSION['login'];
				echo $dadosAnimal['nick'];*/			
			/*Testando se é o usuario da sessão esta acessando o proprio perfil*/
			if((strtolower($_SESSION['login']) == strtolower($dadosAnimal['nick']))){
				$acessoUsuarioSessao = true;
												
				/*Testando se o usuario postou novas mesnagens*/
				if(!empty($_POST['novoPost'])){
					/*$status = new Status();
					// setando codigo do usuario
					$status->setCodigoAnimal($dadosAnimal['codigo']);
					// setando conteudo do post
					$status->setConteudo($_POST['novoPost']);
					// setando a data do post	
					$status->setDataStatus(Status::NOVO_STATUS);
					// inserindo o status
					$modelStatus->inserirStatus($status);*/

					print_r($_POST);
					print_r($_FILES);
					if($_FILES['postPhoto']['error']!="4"){

						// caminho (generico) da pasta do usuario
						$userFolder = "src/img/data_users/".$_SESSION['id']."/";

						// caminho para acesso local à pasta do usuario
						$localPathUserFolder = "../".$userFolder;
						// caminho para acesso local à foto postada pelo usuario
						$localPathUserPhoto = $localPathUserFolder.$_FILES['postPhoto']['name'];
		
						// url para acesso à pasta do usuario para acesso pelo BD
						$urlUserFolder = Init::$urlSources."/".$userFolder;
						// url para acesso à foto postada para acesso pelo BD
						$urlUserPhoto = $urlUserFolder.$_FILES['postPhoto']['name'];
		

						// movendo a foto do usuario para a sua pasta		
						move_uploaded_file($_FILES['postPhoto']['tmp_name'], $localPathUserPhoto);

						$status = new Status();
						// setando codigo do usuario
						$status->setCodigoAnimal($dadosAnimal['codigo']);
						// setando conteudo do post
						$status->setConteudo($_POST['novoPost']);
						//setando a foto
						$status->setFoto($urlUserPhoto);
						// setando a data do post	
						$status->setDataStatus(Status::NOVO_STATUS);
						
						// inserindo o status
						/*$modelStatus->inserirStatus($status);*/

						if(!$modelStatus->inserirStatus($status)){
							header("Location: ".Init::$urlRoot.'/error');
						}
					}

				}
			}

			// Testando a situacao do usuario do perfil e o usuario da sessão (se eles seguem entre si ou não)
			else{
				$acessoUsuarioSessao = false;
				$relacionamento = $this->labelSituationUsers($_SESSION['id'], $dadosAnimal['codigo']);
			}
		}

		else{
			$acessoNaoLogado = true;
		}

		//Carregando os posts e a quantidade
		$posts = $modelStatus->exibirTodosStatus($nick);
		$numeroPosts = $modelStatus->contPosts($nick);

		//verificando se o animal possui posts
		if($dadosAnimal==ModelAnimal::NO_RESULTS){
			$cab->abertura("Pagina não encontrada");
			include_once "../App/Views/mostraUsuario.php"; 	// exibe (ou nao) o nome do usuario logado
			//include_once "../App/Views/formBusca.php";
			include_once "../App/Views/paginaNaoExiste.php";
		}
		else{
			$cab->abertura($dadosAnimal['nome']." - Página Inicial");
			include_once "../App/Views/mostraUsuario.php"; 	// exibe (ou nao) o nome do usuario logado
			//include_once "../App/Views/formBusca.php";
			include_once "../App/Views/animalIndex.php";
		}
		$cab->fechamento();

	}


	/*Meotodo que mostra a situacao de dois usuarios qualquer (Se seguem ou nao entre si)*/
	public function labelSituationUsers($user1, $user2){
		$modelInteracao = new ModelInteracao(Init::getDB());
		$temp = $modelInteracao->situacaoUsuarios($user1, $user2);
		if($temp == $modelInteracao::SEGUINDO)
			return "Seguindo";
		elseif($temp == $modelInteracao::SEG_VOLTA)
			return "Seguir de volta";
		elseif($temp == $modelInteracao::NAO_SEGUE)
			return "Seguir";
		else
			return "ERROR: ";
	}


	//metodo para visualizacao dos posts
	public function verPost($codigo){
		session_start();

		//preparacao dos dados para exibicao
		$cab = new Cabecalho();
		$modelPost = new ModelStatus(Init::getDB());
		$post = $modelPost->exibirUmStatus($codigo);

		//exibindo o post
		$cab->abertura($post['nomeAnimal']);
		include_once "../App/Views/mostraUsuario.php";	// mostrando o usuario
		include_once "../App/Views/exibePost.php";
		$cab->fechamento();
	}

	
	// metodo para cadastar o post de um usuario
	public function newpost($pNick){

		$status = new Status(); // declarando objeto para o status
		$modelStatus = new ModelStatus(Init::getDB()); // declarando objeto do model

		$status->setCodigoAnimal($_POST['codAn']); // objeto status recebendo o codigo do usuario
		$status->setConteudo($_POST['novPost']); // objeto status recebendo o codigo do post
		$status->setDataStatus(Status::NOVO_STATUS);// objeto status recebendo a data do post
		
		$modelStatus->inserirStatus($status); // inserindo o status n banco de dados
	}

	
	public function deletarPost($pCodigo){ //metodo para a exclusao de postagens
		session_start();
		$modelStatus = new ModelStatus(Init::getDB());
		$post = $modelStatus->exibirUmStatus($pCodigo);
		$modelStatus->excluirStatus($pCodigo);
		header("location: ".Init::$urlRoot."/".$_SESSION['login']);	// Retornando para a pagina do usuario
		//echo "deletando post ".$pCodigo;
		//include_once "../App/Views/excluiPost.php";		
	}


	/* metodo ao acessar a pagina /edit do usuario (edição de posts) */
	public function editPost($pCode){
		session_start();
		$head = new Cabecalho();

		$head->abertura("Editar publicacao");
		include_once "../App/Views/mostraUsuario.php";	// mostrando o usuario
		$modelStatus = new ModelStatus(Init::getDB());
		$post = $modelStatus->exibirUmStatus($pCode);	// pegando os dados do status
		if($post['codeUser'] == $_SESSION['id']){	// verificando as credenciais do usuario
			include_once "../App/Views/formEditPost.php";
		}
		else
			echo "vc nao tem autorizacao";
		$head->fechamento();
	}
	
	
	public function countInteractions($user){
		// carregando a quantidades de seguidores/seguidos
		$modelInteracao = new ModelInteracao(Init::getDB());
		return array(
			'followings' => $modelInteracao->contSeguidos($user),
			'followers' => $modelInteracao->contSeguidores($user)
		);
	}


	/* metodo para a listagem dos seguidores */
	public function seguidores($pNick){ 

		session_start();

		$modelAnimal = new ModelAnimal(Init::getDB());
		$dadosAnimal = $modelAnimal->exibirDadosAnimal($pNick); //carregando informacoes do animal
		$modelIntegracao = new ModelInteracao(Init::getDB());
		$seguidores = $modelIntegracao->listarSeguidores($dadosAnimal['codigo']); // carregando a lista de seguidores
		
		$cab = new Cabecalho();
		$cab->abertura($dadosAnimal['nome']); // inserindo o cabecalho com o nome do usuario
		include_once "../App/Views/mostraUsuario.php";
		//include_once "../App/Views/formBusca.php";
		include_once "../App/Views/listarSeguidores.php";	// inserindo a pagina que vai listar os seguidores
		$cab->fechamento();
	}
	
	
	/* Metodo para a listagem dos seguidos */
	public function seguindo($pNick){
		session_start();
		$modelAnimal = new ModelAnimal(Init::getDB());
		$dadosAnimal = $modelAnimal->exibirDadosAnimal($pNick); // carregando informacoes do animal
		$modelIntegracao = new ModelInteracao(Init::getDB()); // carregando a lista de seguidos
		$seguidos = $modelIntegracao->listarSeguidos($dadosAnimal['codigo']);

		$cab = new Cabecalho();
		$cab->abertura($dadosAnimal['nome']); // inserindo o cabecalho com o nome do usuario
		include_once "../App/Views/mostraUsuario.php";
		//include_once "../App/Views/formBusca.php";
		include_once "../App/Views/listarSeguidos.php"; // inserindo a pagina que vai listar os seguidos
		$cab->fechamento();
	}

	/* metodo acionado quando o usuario acessa '/followstate' */
	public function followstate(){
		$modelInteracao = new ModelInteracao(Init::getDB());
		$temp = $this->labelSituationUsers($_SESSION['id'], $dadosAnimal['codigo']);
		echo $temp;
	}


	/*metodo acionado quando o usuario acessa /someactionfollow */
	public function someactionfollow(){
		
		$sessionUser = $_GET['user'];
		// recebendo o codigo do usuario da sessao
		$profileUser = $_GET['prof'];
		// recebendo o codigo do usuario do perfil
		$usersState = $_GET['state'];
		// recebendo o status do relacionamento dos dois usuarios
		$arrayData = null;
		// array responsavel por armazenar os dados requisitados pelos clientes
		$modelFollow = new ModelInteracao(Init::getDB());
		// declaracao de objeto para acesso à classe model
		$relation = new Interacao();
		// declaracao de variavel para acesso aos registros
		
		/*Setando o codigo do seguidor e do seguido no objeto*/
			$relation->setCodigoSeguido($profileUser);
			$relation->setCodigoSeguidor($sessionUser);

		/* Verificando o status dos usuarios (se eles seguem entre si ou nao) */
		if($usersState == "Seguindo"){
			$modelFollow->excluirSeguidor($relation);
			//adicionando a nova ligacao no banco de dados
		}
		elseif($usersState == "Seguir" || $usersState == "Seguir de volta"){
			$modelFollow->adicionarSeguidor($relation);
			//adicionando a nova ligacao no banco de dados
		}
		else{
			echo "ERROR: Erro interno do servidor";
			return false;
		}

		$arrayData = array(
			'indexNewState' => $this->labelSituationUsers($sessionUser, $profileUser),
			'indexCountFollowers' => $modelFollow->contSeguidores($profileUser)
		);
		$arrayJsonData = json_encode($arrayData);
		//	codificando array que enviará os dados em formato JSON
		echo $arrayJsonData;
		// array em formato JSON sendo apresentada

	}


	// executa quando o usuario entra na pagina /setup do usuario
	public function setup($pNick){
		session_start();
		$cab = new Cabecalho();
		if(isset($_SESSION['login']) && $_SESSION['login'] == $pNick){	// verificando se o usuario esta logado
			$modelAnimal = new ModelAnimal(Init::getDB());
			$dadosAnimal = $modelAnimal->exibirDadosAnimal($pNick);	// carregando informacoes do animal
			$cab->abertura($dadosAnimal['nome']." - Configurações");
			include_once "../App/Views/mostraUsuario.php";
			include_once "../App/Views/formUpdateData.php";	// exibinindo os dados do usuario no formulario para atualizacao
		}
		else{
			$cab->abertura("Acesso negado");
			include_once "../App/Views/mostraUsuario.php";
			echo "Acesso negado!!!!";
		}
		$cab->fechamento();
	}


	/* Metodo que recebe os dados inseridos e gerencia a atualizacao dos dados */
	public function updateData($pArrayDataUser){
		session_start();
		$modelUser = new ModelAnimal(Init::getDB());
		$objUser = new Animal();

		$objUser->setCodigo($_SESSION['id']);
		
		/*// atribuindo o caminho da pasta do usuario
		$folderUser = "/src/img/data_users/".$objUser->getCodigo()."/";
		// variavel para acesso local ao servidor
		$serverLocalDir = "..";
		// atribuindo o caminho (endereco da pasta + endereco da foto)
		$photoPath = $folderUser.$_FILES['foto']['name'];
		// movendo a foto para a pasta local do usuario
		move_uploaded_file($_FILES['foto']['tmp_name'],$serverLocalDir.$photoPath);
		*/
		/* Carregando os dados digitados pelo usuario*/
		$objUser->setNick($_SESSION['login']);
		$objUser->setNome($pArrayDataUser['name']);
		$objUser->setDescricao($pArrayDataUser['description']);
		$objUser->setEmail($pArrayDataUser['email']);
		//$objUser->setFoto(Init::$urlSources.$photoPath);
		$objUser->setSenha($_SESSION['senha']);
		//$objUser->setNascimento($pArrayDataUser['birthDate']);

		// Testando se os dados foram alterados
		if($modelUser->alterarDadosAnimal($objUser)){

			/*if(!file_exists($folderUser)){
				mkdir($folderUser, 0775);
			}*/
		//	$folderUser = "../src/img/data_users/".$_SESSION['id'];
			//echo "perfil salvo";
		//	move_uploaded_file($_FILES['foto']['tmp_name'],$folderUser);

			// Atualizando o novo login
			$_SESSION['login'] = $objUser->getNick();
			// redirecionamento para a pagina do usuario
			//header("location: ".Init::$urlRoot."/".$_SESSION['login']);
			header("location: ".Init::$urlRoot."/".$_SESSION['login']."/setup");
		}
		else 
			echo "erro";
	}

	/* Metodo para atualizar a foto de perfil */
	public function updatePhoto($pNewData){
		session_start();
		$modelUser = new ModelAnimal(Init::getDB());
		
		/* Testando se a foto foi efetivamente atualizada */
		if($modelUser->changeProfilePhoto($pNewData, $_SESSION['id'])){
			//header("location: ".Init::$urlRoot."/".$_SESSION['login']);
			header("location: ".Init::$urlRoot."/".$_SESSION['login']."/setup");
		}else{
			header("location: ".Init::$urlRoot."/error");
		}
	}


	/* metodo para atualizacao de credenciais do usuario (user e senha) */
	public function updateCredentials($pArrayDataUser){
		session_start();
		$modelUser = new ModelAnimal(Init::getDB());
		$modelUser->changeCredentials($pArrayDataUser['user'], $pArrayDataUser['newPassword'], $_SESSION['id']);	// mudando a senha do usuario no BD

		/*atualizando as novas credenciais*/
		$_SESSION['senha'] = $pArrayDataUser['newPassword'];
		$_SESSION['login'] = $pArrayDataUser['user'];

		// encaminhamento para a pagina de perfil
		//header("location: ".Init::$urlRoot."/".$_SESSION['login']);
		header("location: ".Init::$urlRoot."/".$_SESSION['login']."/setup");
	}

	/* metodo que encaminha os novos dados para atualização no banco*/
	public function updatePost($arrayDataPost){
		session_start();
		$modelPost = new ModelStatus(Init::getDB());
		// declaracao do objeto que conterá os novos dados
		$post = new Status();
		// setando codigo do post
		$post->setCodigo($arrayDataPost['formCodePost']);
		// setando o novo conteudo do post
		$post->setConteudo($arrayDataPost['formContentPost']);
		// verificando se o post a ser atualizado pertence mesmo ao usuario corrente
		$check = $modelPost->isItUserPost($_SESSION['id'], $arrayDataPost['formCodePost']);	
		
		if($check){
			// post atualizado
			$modelPost->atualizarStatus($post);
			// usuario é encaminhado para a pagina inicial
			header("location: ".Init::$urlRoot.'/'.$_SESSION['login']);
		}
		else
			echo "ERRO INTERNO";
	}

	public function deleteUser(){
		session_start();
		/*javascript need to confirm */
		$modelUser = new ModelAnimal(Init::getDB());
		if($modelUser->excluir($_SESSION['id']))
			header("location: ".Init::$urlRoot.'/logout');
		else
			header("location: http://hardware.com");
	}

	public function opSeguindo(){
		echo "seguido";
	}
}
?>