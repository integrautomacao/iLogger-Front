var app = angular.module('app', ['ngRoute', 'controllers', 'services']);

app.run(function($rootScope,$location,$http,MyMessage,Requisicao) {

    $rootScope.goTo = function(pagina){
      $location.path(pagina);
    };

    $rootScope.logado = true;
    $rootScope.menuOpened = false;
    $rootScope.logout = function () {
      $location.path('/login');
    }

    $rootScope.imageSrc = localStorage.getItem("in-usuariofoto");

    $rootScope.toggleMenu =  function(event){
      console.log('toggleMenu');
      $rootScope.menuOpened = !$rootScope.menuOpened;
      if(event){
        event.stopPropagation();
      }
    }
    $rootScope.closeMenu =  function(event){
      $rootScope.menuOpened = false;
      if(event){
        event.stopPropagation();
      }
    }
    $rootScope.openMenu =  function(event){
      $rootScope.menuOpened = true;
      if(event){
        event.stopPropagation();
      }
    }

});

app.config(['$routeProvider', '$locationProvider', function($routeProvider, $locationProvider) {
  $routeProvider
        .when('/login', {
            controller: "LoginCtrl",
            templateUrl: "view/login.html",
        })
        .when('/home', {
            controller: "HomeCtrl",
            templateUrl: "view/home.html",
        })
        .when('/meusdados', {
            controller: "MeusDadosCtrl",
            templateUrl: "view/meusdados.html",
        })
        .when('/relatorio/:tiporel', {
            controller: "RelatorioCtrl",
            templateUrl: "view/relatorio.html",
        })
        .otherwise({
            redirectTo: '/home'
        });


  API_URL = "../api/index.php";
}]);
