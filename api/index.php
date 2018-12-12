<?php

header("Content-Type: application/json", true);
error_reporting(E_ERROR | E_PARSE);


// ########################## BD ###############################################
// ### local
$user = 'sa';
$password = 'PlantPAx4';
$db = 'Ilogger';
$host = 'EWS01\FTVIEWX64TAGDB';
// #############################################################################
$post = file_get_contents('php://input');
$post =  json_decode($post);
$post =  (array) $post;
date_default_timezone_set('America/Sao_Paulo');
$date = date('Y-m-d H:i');

// TESTE 1
// $teste = @mssql_connect($dbhost,$user,$password) or die("Não foi possível a conexão com o servidor!");
// $teste2 = @mssql_select_db("$db") or die("Não foi possível selecionar o banco de dados!");


// TESTE 2

$conninfo = array("Database" => $db, "UID" => $user, "PWD" => $password);
$conn = sqlsrv_connect($host, $conninfo) or die('erro');
// $params = array();
// $options =array("Scrollable" => SQLSRV_CURSOR_KEYSET);
// $consulta = sqlsrv_query($conn, "SELECT * FROM tblTag") or die('erro');
// while( $obj = sqlsrv_fetch_object($consulta )) {
//   echo($obj->tag);
// }

// TESTE 3
// try {
//   $pdo = new PDO ("mssql:host=$host;dbname=$db","$user","$password");
// } catch (PDOException $e) {
//   echo "Erro de Conexão " . $e->getMessage() . "\n";
//   exit;
// }
// $query = $pdo->prepare("SELECT [id] FROM [Ilogger].[dbo].[tblTag]");
// // $query = $pdo->prepare("SELECT [id] FROM [Ilogger].[dbo].[tblTag]");
// $query->execute();

// for($i=0; $row = $query->fetch(); $i++){
// echo $i." – ".$row['Coluna']."<br/>";
// }

// unset($pdo);
// unset($query);


if($post){

  if($post["method"]){
    switch ($post["method"]) {

      case 'logarUsuario':

      if(!isset($post['login'])){
        sendResponse('Login não informado',1);
      }
      if(!isset($post['senha'])){
        sendResponse('Senha não informada',1);
      }
      $login = $post['login'];
      $senha = $post['senha'];

      $conninfo = array("Database" => $db, "UID" => $user, "PWD" => $password);
      $conn = sqlsrv_connect($host, $conninfo) or die('erro');

     $sql = sqlsrv_query($conn, "SELECT * FROM tblUsuarios WHERE login = '".$login."' AND senha = '".$senha."' ") or die('erro');

      if($sql){

        // $retorno = sqlsrv_fetch_object($sql);
        $retorno = sqlsrv_fetch_array($sql, SQLSRV_FETCH_ASSOC);
        // while( $obj = sqlsrv_fetch_object($sql )) {
          // $retorno[] = $obj;
          // print_r($obj);
        // }
      // die($retorno);

        if($retorno){
          sendResponse($retorno);
        }else{
          sendResponse('Usuário ou senha inválidos',1);
        }
      }


      break;
      case 'carregaPhoto':
        $id = $post['id'];
        if($id < 0){
          $erro = json_encode(array('erro' => 'Código de Usuário não encontrado' ));
          die($erro);
        }

        $my_file = '../usuariofoto/'.$id.'.txt';
        $handle = fopen($my_file, 'r') or die(json_encode(array('erro' => 'Foto não encontrada' )));
        $conteudo = fread($handle, filesize($my_file));
        fclose($handle);
        $sucesso = array('sucesso' => 'Arquivo encontrado', 'photo' => $conteudo );
        sendResponse($sucesso);

      break;
      case 'editarMeusDados':
          

          $aux = json_decode($post['data'],1);

          if(!isset($aux['nome'])){
            sendResponse('Nome não informado',1);
          }
          if(!isset($aux['login'])){
            sendResponse('Login não informado',1);
          }

          $alteracaoSenha = '';
          if(isset($aux['novasenha'])){
            if($aux['novasenha'] == $aux['resenha']){
              $novaSenha = $aux['novasenha'];
              $sqlSenha = ", senha = '".$novaSenha."' ";
            }
          }

          $conninfo = array("Database" => $db, "UID" => $user, "PWD" => $password);
          $conn = sqlsrv_connect($host, $conninfo) or die('erro');


            $sql = sqlsrv_query($conn, "UPDATE tblUsuarios SET nome = '".$aux['nome']."', login = '".$aux['login']."' ".$sqlSenha." WHERE id = '".$aux['id']."' ");
            if($sql){
              sendResponse($retorno);
            }else{
              sendResponse('Falha na atualizacao do BD',1);
            }

          mysql_close($link);
      break;
      case 'gerarRelatorio':
        

        $tipoRelatorio = $post['tipo'];

        $where = "";
        switch ($tipoRelatorio) {
          case 'horas-trabalhadas':
            $where = " WHERE tblTag.tag = 'Qtde_Horas_Trabalhadas_EL01' OR tblTag.tag = 'Qtde_Horas_Trabalhadas_EL02' ";
          break;
          case 'horas-paradas':
            $where = " WHERE tblTag.tag = 'Qtde_Horas_Paradas_EL01' OR tblTag.tag = 'Qtde_Horas_Paradas_EL02' ";
          break;
          case 'falhas':
            $where = " WHERE tblTag.tag = 'Qtde_Falhas_EL01' OR tblTag.tag = 'Qtde_Falhas_EL02' ";
          break;
          case 'producao':
            $where = " WHERE tblTag.tag = 'IC_EL_01_TONH' OR tblTag.tag = 'IC_EL_02_TONH' ";
          break;
          case 'dados-processo':
            $where = " WHERE tblTag.tag = 'IC_EL_01_PV' OR tblTag.tag = 'IC_EL_02_PV' ";
          break;
          default:
            $erro = json_encode(array('erro' => 'Esse tipo de relatório não existe!' ));
            die($erro);
          break;
        }


        $conninfo = array("Database" => $db, "UID" => $user, "PWD" => $password);
        $conn = sqlsrv_connect($host, $conninfo) or die('erro');

        $sql = sqlsrv_query($conn, "SELECT tblTag.*, tv.valor, tv.dataAtu FROM tblTag INNER JOIN tblTag_valor AS tv ON tv.tag = tblTag.id".$where);
        // die("SELECT tblTag.*, tv.valor, tv.dataAtu FROM tblTag INNER JOIN tblTag_valor AS tv ON tv.tag = tblTag.id".$where);

        if($sql){
          $ponteiro = 0;
            while($linha = sqlsrv_fetch_array($sql, SQLSRV_FETCH_ASSOC)){
              $retorno['relatorio'][$linha['tag']][] = $linha;
          }
          sendResponse($retorno);
        }
      break;


      default:
      // ##############################
        echo 'MÉTODO NÃO DEFINIDO';
        break;
    }


  }else{
    echo 'MÉTODO NÃO DEFINIDO';
  }
}else{
  echo 'NO ONE POSITION SENDED';
}

function sendCurl($url,$data=null){

  global $identidade, $credencial, $appKey, $api_version;

  $ch = curl_init();

  curl_setopt($ch, CURLOPT_URL,$url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POST, 1); // seta o tipo de envio como POST

  if(!$data){
    $data = array(
      'identidade' => $identidade,
      'credencial' => $credencial,
      'appKey' => $appKey,
      'version' => $api_version
    );
  }else{
    $data['identidade'] = $identidade;
    $data['credencial'] = $credencial;
    $data['appKey'] = $appKey;
    $data['version'] = $api_version;

  }

  if(isset($data['data'])){
    $data['data'] = json_encode($data['data']);
  }

  curl_setopt($ch, CURLOPT_POSTFIELDS,
           $data);

  $server_output = curl_exec ($ch);

  curl_close ($ch);

  // further processing ....
  if ($server_output){
    die($server_output);
  }else{
    echo 'API REQUEST NOT WORKS';
  }


}

function sendResponse($data,$erro=null){
  if($erro){
    $array = json_encode(array('erro' => $data ));
    die($array);
  }else{
    $data['sucesso'] = true;
    $array = json_encode($data);
    die($array);
  }
}

?>
