var app = angular.module('services', []);

app.factory('MyLoading', function(){
  return {
    show:function(msg){
      var el = document.getElementById("myLoading");
      var el2 = document.getElementById("msg-loading");
      if(msg){
        el2.innerHTML = msg;
      }
      el.style.display = "block";

    },
    hide:function(){
      var el = document.getElementById("myLoading");
      el.style.display = "none";
    }
  }
})
app.factory('MyMessage', function(){
  return {
    show:function(type, msg, temporary){
      if(temporary){
        // fazer sumir no seTime
        var tempoT = 1000;
        if(temporary > 1){
          tempoT = temporary*1000;
        }
        setTimeout(function(){
          el.style.display = "none";
        },tempoT)
      }
      var el = document.getElementById("myMessage");
      var el2 = document.getElementById("innerMsg");
      var x = document.getElementById("x");
      // fazer a msg aparecer (display)
      el.style.display = "block";
      //setar o conteudo da msg
      el2.innerHTML = msg;
      // setar a classe do tipo de mensagem
      if(type == 'error'){
        el.className = el.className+" errorMessage";
        var cn = el.className.replace('successMessage','errorMessage');
        el.className = cn;
      }else{
        el.className = el.className+" successMessage";
        var cn = el.className.replace('errorMessage','successMessage');
        el.className = cn;
      }
      // adicionar evento de fechar no x
      x.addEventListener('click', function() {
        el.style.display = "none";
      });
    },
    hide:function(){
      var el = document.getElementById("myMessage");
      el.style.display = "none";
    }
  }
})

app.service('Requisicao', function($http,MyMessage,MyLoading,$rootScope){
  return{
    post: function(method,data,callbackSuccess,callbackError) {

      if(!method){
        console.log('PASSAR METHOD PARA REQUISICAO POST');
        return true;
      }
      if(data){
        data.method = method;
      }else{
        var data = {};
        data.method = method;
      }

      $http({
        method: 'POST',
        url: API_URL,
        data: data
      }).then(function successCallback(response) {
          // console.log('response of Requisicao');
          // console.log(response);
          if(response.data.erro){
            MyMessage.show('error', response.data.erro);
            MyLoading.hide();
            return true;
          }else if(response.data.sucesso){
            callbackSuccess(response);
          }else{
            MyMessage.show('error', 'Erro interno do servidor. Contato o suporte.');
            MyLoading.hide();
            return true;
          }
      }, function errorCallback(response) {
        // console.log('error');
        // console.log('response: ', response);
        if(callbackError){
          MyLoading.hide();
          callbackError(response);
        }else{
          MyMessage.show('error', 'Falha na conexão. Tente novamente mais tarde.');
          MyLoading.hide();
          return true;
        }
      });

    }
    ,uploadPhoto: function(photo) {
      $http({
        method: 'POST',
        url: API_URL,
        data: {'method':'uploadPhoto','photo': photo, 'cdClienteErp': $rootScope.cdClienteErp}
      }).then(function successCallback(response) {
        if(response.data.sucesso){
          localStorage.setItem("mk-usuariofoto", photo);
          MyMessage.show('sucess', response.data.sucesso, 2);
          MyLoading.hide();
        }
      }, function errorCallback(response) {
      });
    }
    ,carregaPhoto: function(id, callback, callbackError) {
      $http({
        method: 'POST',
        url: API_URL,
        data: {'method':'carregaPhoto', 'id': id}
      }).then(function successCallback(response) {
        if(response.data.sucesso){
          callback(response.data.photo);
        }else{
          callbackError();
        }
      }, function errorCallback(response) {
          callbackError();
      });
    }
  }
});

app.directive("ngFileSelect", function(fileReader, $timeout) {
    return {
      scope: {
        ngModel: '='
      },
      link: function($scope, el) {
        function getFile(file) {
          fileReader.readAsDataUrl(file, $scope)
            .then(function(result) {
              $timeout(function() {
                $scope.ngModel = result;
              });
            });
        }

        el.bind("change", function(e) {
          var file = (e.srcElement || e.target).files[0];
          getFile(file);
        });
      }
    };
  });

app.factory("fileReader", function($q, $log, Requisicao, $rootScope) {
  var onLoad = function(reader, deferred, scope) {
    return function() {
      // console.log('Upou!!');
      // console.log(reader.result);
      Requisicao.uploadPhoto(reader.result);
      scope.$apply(function() {
        deferred.resolve(reader.result);
        $rootScope.imageSrc = reader.result;
      });
    };
  };

  var onError = function(reader, deferred, scope) {
    return function() {
      scope.$apply(function() {
        deferred.reject(reader.result);
      });
    };
  };

  var onProgress = function(reader, scope) {
    return function(event) {
      scope.$broadcast("fileProgress", {
        total: event.total,
        loaded: event.loaded
      });
    };
  };

  var getReader = function(deferred, scope) {
    var reader = new FileReader();
    reader.onload = onLoad(reader, deferred, scope);
    reader.onerror = onError(reader, deferred, scope);
    reader.onprogress = onProgress(reader, scope);
    return reader;
  };

  var readAsDataURL = function(file, scope) {
    var deferred = $q.defer();

    var reader = getReader(deferred, scope);
    if(file){
      reader.readAsDataURL(file);
    }

    return deferred.promise;
  };

  return {
    readAsDataUrl: readAsDataURL
  };
});
