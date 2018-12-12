var app = angular.module('controllers', []);

app.controller('LoginCtrl', function ($scope, $location, $rootScope, Requisicao, MyLoading, MyMessage) {
  $rootScope.closeMenu();
  $rootScope.logado = false;
  $rootScope.showBtVoltar = false;
  localStorage.removeItem("in-usuario");
  localStorage.removeItem("in-usuariofoto");


  $scope.entrar = function () {

    MyLoading.show('Consultando suas informações');

    if(!$scope.login){
      MyMessage.show('error', 'Digite o Login', 2);
      MyLoading.hide();
      return true;
    }
    if(!$scope.senha){
      MyMessage.show('error', 'Digite a senha', 2);
      MyLoading.hide();
      return true;
    }

    var senha = $scope.senha;

    Requisicao.post('logarUsuario',{'login': $scope.login, 'senha': senha}, function(response){
        if(response.data.id){
          var aux = response.data;
          localStorage.setItem("in-usuario", JSON.stringify(aux));

          pegarUsuarioFoto(aux.id);
        }
    });


    function pegarUsuarioFoto(id){
      Requisicao.carregaPhoto(id,function(photo){
        localStorage.setItem("in-usuariofoto", photo);
        MyLoading.hide();
        MyMessage.hide();
        $location.path('/home');
      }, function(error){
        MyLoading.hide();
        MyMessage.hide();
        $location.path('/home');
      });

    }

  } // end funcao entrar


});

app.controller('MeusDadosCtrl', function ($scope, $rootScope, $location, $http, MyLoading, MyMessage, Requisicao, fileReader) {

  $rootScope.showBtVoltar = true;
  $rootScope.closeMenu();

  var usuario = JSON.parse(localStorage.getItem("in-usuario"));
  var usuariofoto = localStorage.getItem("in-usuariofoto");

  if (usuario) {
    $rootScope.logado = true;
    $rootScope.usuario = usuario;
    $rootScope.idUsuario = usuario.id;
    $rootScope.nomeUsuario = usuario.nome;
    $rootScope.imageSrc = usuariofoto;
  }else{
    $location.path('/login');
  }

  $scope.editarDados = function(){
      if(!$scope.usuario.nome){
        MyLoading.hide();
        MyMessage.show('error', 'Informe o campo Nome',2);
        return true;
      }
      if(!$scope.usuario.login){
        MyLoading.hide();
        MyMessage.show('error', 'Informe o campo Login',2);
        return true;
      }
      if($scope.usuario.novasenha){
        if(!$scope.usuario.resenha){
          MyLoading.hide();
          MyMessage.show('error', 'Informe o campo Repita a Senha',3);
          return true;
        }
      }

      if($scope.usuario.novasenha != $scope.usuario.resenha){
        MyLoading.hide();
        MyMessage.show('error', 'As senhas digitadas devem ser iguais',3);
        return true;
      }

    MyLoading.show('Salvando alterações..');

    Requisicao.post('editarMeusDados',{'data': JSON.stringify($scope.usuario) }, function(response){
        console.log('sucesso editarMeusDados');
        console.log('response: ',response);
        $scope.usuario.novasenha = '';
        $scope.usuario.resenha = '';

        localStorage.setItem("in-usuario", JSON.stringify($scope.usuario));
        MyMessage.show('sucess', "Dados atualizados com sucesso!", 2);
        MyLoading.hide();
        return true;

    });


  } // end function editarDados


 // ########### Foto ########################

  $scope.$on("fileProgress", function(e, progress) {
    $scope.progress = progress.loaded / progress.total;
  });

  $scope.triggerClick2File = function(){
    document.getElementById('inputFilePhoto').click();
  }
  // #######################################

});

app.controller('HomeCtrl', function ($scope, $rootScope, $location, MyLoading, MyMessage) {

  $rootScope.closeMenu();
  $rootScope.showBtVoltar = false;

  var usuario = $scope.usuario = JSON.parse(localStorage.getItem("in-usuario"));
  var usuariofoto = localStorage.getItem("in-usuariofoto");
  if (usuario) {
    $rootScope.logado = true;
    $rootScope.idUsuario = usuario.id;
    $rootScope.nomeUsuario = usuario.nome;
    $rootScope.imageSrc = usuariofoto;
    MyLoading.hide();
    MyMessage.hide();
  }else{
    $location.path('/login');
  }

});

app.controller('RelatorioCtrl', function ($scope, $rootScope, $location, MyLoading, MyMessage, Requisicao, $routeParams) {
  $rootScope.closeMenu();

  $rootScope.showBtVoltar = true;

  var usuario = $scope.usuario = JSON.parse(localStorage.getItem("in-usuario"));
  var usuariofoto = localStorage.getItem("in-usuariofoto");
  if (usuario) {
    $rootScope.logado = true;
    $rootScope.idUsuario = usuario.id;
    $rootScope.nomeUsuario = usuario.nome;
    $rootScope.imageSrc = usuariofoto;
    MyLoading.hide();
    MyMessage.hide();
  }else{
    $location.path('/login');
    return true;
  }

  if(usuario.cargo == 1){
    if($routeParams.tiporel != 'horas-trabalhadas' && $routeParams.tiporel != 'falhas'){
      MyMessage.show('error', 'Seu usuário não tem acesso a esse relatório');
      return true;
    }
  }
  if(usuario.cargo == 2){
    if($routeParams.tiporel != 'horas-paradas' && $routeParams.tiporel != 'producao' && $routeParams.tiporel != 'dados-processo'){
      MyMessage.show('error', 'Seu usuário não tem acesso a esse relatório');
      return true;
    }
  }


  MyLoading.show('Consultando suas informações');

  // ## Montagem do array padrao
  var options = {
    chart: {
      type: 'line',
      height: 450
    },
    series: [
    ],
    xaxis: {
      categories: [],
      type: "datetime"
    },
    yaxis: {
      min: 0,
      max: 200,
    },
    title: {
      text: $routeParams.tiporel,
      align: 'left'
    }
  }

  // Buscar o relatorio
  Requisicao.post('gerarRelatorio',{'tipo': $routeParams.tiporel}, function(response){
      if(response.data.relatorio){
          $scope.vazio = false;
          var series = [];
          var categories = [];

          // ## Colocar os dados no padrão necessário ao plugin de gráficos
          for(var i in response.data.relatorio){
            for(var j in response.data.relatorio[i]){
              series.push(parseFloat(response.data.relatorio[i][j].valor));
              categories.push(response.data.relatorio[i][j].dataAtu.date);
            }
            // ## Dados das linhas
            options.series.push(
              {
                name: i,
                data: series
              }
            );
            series = [];
          }
          // ## Eixo X
          options.xaxis.categories = categories;

          // ## renderizar o gráfico
          var chart = new ApexCharts(document.querySelector("#chart"), options);
          chart.render();

          MyLoading.hide();
          MyMessage.hide();

      }else{
        MyLoading.hide();
        $scope.vazio = true;
      }
  });


});
