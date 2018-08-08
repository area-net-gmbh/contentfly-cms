(function() {
    'use strict';

    angular
        .module('app')
        .run(run);

    function run($rootScope, $location, $cookies, localStorageService, $http, $uibModal){


        if ($cookies.get('APPCMS-TOKEN') != null) {
            $http.defaults.headers.common = {
                'APPCMS-TOKEN': $cookies.get('APPCMS-TOKEN')
            };
        }

        $rootScope.toast = function(text){
          var x = document.getElementById("toast");
          x.innerHTML = text;
          x.className = "show";
          setTimeout(function(){ x.className = x.className.replace("show", ""); }, 3000);
        };

        $rootScope.openPasswordForm = function(){


          var modalInstance = $uibModal.open({
            templateUrl: '/ui/default/views/partials/password.html?v=' + APP_VERSION,
            controller: 'PasswordCtrl as vm',
            resolve: {
              object: function(){ return $rootScope.user; }
            },
            backdrop: 'static',
            size: 'xl'
          });


          modalInstance.result.then(
            function () {
              $rootScope.toast("Das Passwort wurde erfolgreich geändert.");
            },
            function () {
            }
          );
        };

        $rootScope.$on( "$routeChangeStart", function(event, next, current) {

            if(next.originalPath != '/error' ) {
              $http({
                method: 'GET',
                url: '/api/config',
                headers: {
                  'Content-Type': 'application/json'
                },
                data: ''
              }).then(function successCallback(response) {
                if ($rootScope.version != response.data.version) {
                  $rootScope.newVersion = response.data.version;
                } else {
                  $rootScope.newVersion = null;
                }
              }, function errorCallback(response) {
                $rootScope.newVersion = null;
                $rootScope.error = response.data.message;
                $location.path("/error");
              });
            }

            if(next.secure){
                //localStorageService.set('localStorageKey','Add this!');
                if ($cookies.get('APPCMS-TOKEN') == null) {
                    $location.path("/login");
                }else{
                    $rootScope.userLoggedIn = true;
                    $rootScope.user = localStorageService.get('user');
                    $rootScope.version = localStorageService.get('version');
                    $rootScope.devmode = localStorageService.get('devmode');
                    $rootScope.frontend = localStorageService.get('frontend');
                    $rootScope.schema = localStorageService.get('schema');
                    $rootScope.permissions = localStorageService.get('permissions');
                    $rootScope.i18nPermissions = localStorageService.get('i18nPermissions');
                    $rootScope.uiblocks = localStorageService.get('uiblocks');

                    var entities = {};
                    for (var entity in $rootScope.schema) {
                        if(entity == '_hash') continue;
                        if(entity.substr(0, 4) == 'PIM\\' || $rootScope.schema[entity]["settings"]["hide"] || !$rootScope.permissions[entity]["readable"]) continue;
                        entities[entity] = $rootScope.schema[entity]["settings"]["label"];
                    }

                    $rootScope.entities = entities;

                }
            }else{
                $rootScope.frontend = localStorageService.get('frontend');
            }
        });
    }

})();
