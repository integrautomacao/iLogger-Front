<?php
//  phpinfo();
header("Content-Type: application/json", true);
error_reporting(E_ERROR | E_PARSE);


// ########################## BD ###############################################
// ### local
$user = 'root';
$password = 'root';
$db = 'infactory';
$host = '127.0.0.1';
// $host = '127.0.0.1:8889';
// #############################################################################
$post = file_get_contents('php://input');
$post =  json_decode($post);
$post =  (array) $post;
date_default_timezone_set('America/Sao_Paulo');
$date = date('Y-m-d H:i');



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

      $link = mysql_connect(
         $host
         ,$user
         ,$password
      ) or die('Erro de conexao com BD');
      $db_selected = mysql_select_db(
         $db
         ,$link
      ) or die('BD not found');

      $sql = mysql_query("SELECT * FROM usuario WHERE login = '".$login."' AND senha = '".$senha."' ");

      if($sql){
        $retorno = mysql_fetch_assoc($sql);
        if($retorno){
          sendResponse($retorno);
        }else{
          sendResponse('Usuário ou senha inválidos',1);
        }
      }

      mysql_close($link);

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
          $link = mysql_connect(
            $host
            ,$user
            ,$password
          ) or die('Erro de conexao com BD');
            $db_selected = mysql_select_db(
              $db
              ,$link
          ) or die('BD not found');

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

            $sql = mysql_query("UPDATE usuario SET nome = '".$aux['nome']."', login = '".$aux['login']."' ".$sqlSenha." WHERE id = '".$aux['id']."' ");
            if($sql){
              sendResponse($retorno);
            }else{
              sendResponse('Falha na atualizacao do BD',1);
            }

          mysql_close($link);
      break;
      case 'gerarRelatorio':
        $link = mysql_connect(
           $host
           ,$user
           ,$password
        ) or die('Erro de conexao com BD');
        $db_selected = mysql_select_db(
           $db
           ,$link
        ) or die('BD not found');

        $tipoRelatorio = $post['tipo'];

        $where = "";
        switch ($tipoRelatorio) {
          case 'horas-trabalhadas':
            $where = " WHERE tag.tag = 'Qtde_Horas_Trabalhadas_EL01' OR tag.tag = 'Qtde_Horas_Trabalhadas_EL02' ";
          break;
          case 'horas-paradas':
            $where = " WHERE tag.tag = 'Qtde_Horas_Paradas_EL01' OR tag.tag = 'Qtde_Horas_Paradas_EL02' ";
          break;
          case 'falhas':
            $where = " WHERE tag.tag = 'Qtde_Falhas_EL01' OR tag.tag = 'Qtde_Falhas_EL02' ";
          break;
          case 'producao':
            $where = " WHERE tag.tag = 'IC_EL_01_TONH' OR tag.tag = 'IC_EL_02_TONH' ";
          break;
          case 'dados-processo':
            $where = " WHERE tag.tag = 'IC_EL_01_PV' OR tag.tag = 'IC_EL_02_PV' ";
          break;
          default:
            $erro = json_encode(array('erro' => 'Esse tipo de relatório não existe!' ));
            die($erro);
          break;
        }

        $sql = mysql_query("SELECT tag.*, tv.valor, tv.dataAtu FROM tag INNER JOIN tag_valor tv ON tv.tag = tag.id".$where);

        if($sql){
          $ponteiro = 0;
          while($linha = mysql_fetch_assoc($sql)){
              $retorno['relatorio'][$linha['tag']][] = $linha;
          }
          sendResponse($retorno);
        }
        mysql_close($link);
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
