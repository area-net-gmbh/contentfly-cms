(function() {
    'use strict';

    angular
        .module('app')
        .controller('SettingsCtrl', SettingsCtrl);

    function SettingsCtrl($scope, $cookies, $location, localStorageService, $stateParams, $http, $rootScope, $uibModal, TokenService, EntityService, $extend){
        var vm  = $extend ? $extend : this;

        //Properties
        vm.message              =  '';
        vm.buttonsDisabled      = true;
        vm.tokenSubmitDisabled  = true;
        vm.tokenReferrer        = null;
        vm.tokenUser            = null;
        vm.token                = null;
        vm.tokens               = [];
        vm.users                = [];

        $rootScope.currentNav = 'Admin\\Settings';

        //Functions
        vm.addToken             = addToken;
        vm.checkTokenSubmittable= checkTokenSubmittable;
        vm.deleteToken          = deleteToken;
        vm.flushSchemaCache     = flushSchemaCache;
        vm.generateToken        = generateToken;
        vm.loadSchema           = loadSchema;
        vm.updateDatabase       = updateDatabase;
        vm.validateORM          = validateORM;

        //Init
        init();

        ///////////////////////////////////

        function addToken(){
            TokenService.add(vm.tokenReferrer, vm.token, vm.tokenUser).then(
                function successCallback(response) {
                    vm.tokens.push(response.data.message);

                    vm.tokenReferrer = null;
                    vm.token = null;
                    vm.tokenSubmitDisabled = true;
                },
                function errorCallback(response) {
                    if(response.status == 401){
                        var modalInstance = $uibModal.open({
                            templateUrl: '/ui/default/views/partials/relogin.html?v=' + APP_VERSION,
                            controller: 'ReloginCtrl as vm',
                            backdrop: 'static'
                        });

                        modalInstance.result.then(
                            function () {
                                vm.delete(object);
                            },
                            function () {
                                $uibModalInstance.close();
                                $location.path('/logout');
                            }
                        );

                    }else{
                        var modalInstance = $uibModal.open({
                            templateUrl: '/ui/default/views/partials/modal.html?v=' + APP_VERSION,
                            controller: 'ModalCtrl as vm',
                            resolve: {
                                title: function(){ return 'Fehler beim Anlegen'; },
                                body: function(){ return response.data.message; },
                                hideCancelButton: true
                            }
                        });
                    }
                }
            );
        }

        function deleteToken(index){
            var modalInstance = $uibModal.open({
                templateUrl: '/ui/default/views/partials/modal.html?v=' + APP_VERSION,
                controller: 'ModalCtrl as vm',
                resolve: {
                    title: function(){ return 'Token löschen'; },
                    body: function(){ return 'Wollen Sie den Token wirklich löschen? '; },
                    object: function(){ return null; },
                    hideCancelButton: false
                }
            });

            modalInstance.result.then(
                function (doDelete) {
                    if(doDelete){
                        var id = vm.tokens[index].id;

                        TokenService.delete(id).then(
                            function successCallback(response) {
                                vm.tokens.splice(index, 1);
                            },
                            function errorCallback(response) {
                                if(response.status == 401){
                                    var modalInstance = $uibModal.open({
                                        templateUrl: '/ui/default/views/partials/relogin.html?v=' + APP_VERSION,
                                        controller: 'ReloginCtrl as vm',
                                        backdrop: 'static'
                                    });

                                    modalInstance.result.then(
                                        function () {
                                            vm.delete(object);
                                        },
                                        function () {
                                            $uibModalInstance.close();
                                            $location.path('/logout');
                                        }
                                    );

                                }else{
                                    var modalInstance = $uibModal.open({
                                        templateUrl: '/ui/default/views/partials/modal.html?v=' + APP_VERSION,
                                        controller: 'ModalCtrl as vm',
                                        resolve: {
                                            title: function(){ return 'Fehler beim Löschen'; },
                                            body: function(){ return response.data.message; },
                                            hideCancelButton: true
                                        }
                                    });
                                }
                            }
                        );


                    }
                },
                function () {

                }
            );

        }

        function checkTokenSubmittable(){
            if(vm.tokenReferrer && vm.token){
                vm.tokenSubmitDisabled = false;
            }else{
                vm.tokenSubmitDisabled = true;
            }
        }

        function flushSchemaCache(){
            vm.buttonsDisabled = true;
            printMessage('Schema-Cache wird geleert..', false);

            $http({
                method: 'POST',
                url: '/system/do',
                data:{ method: 'flushSchemaCache'}
            }).then(function successCallback(response) {
                printMessage(response.data.message, true);
                vm.buttonsDisabled = false;
            }, function errorCallback(response) {
                vm.buttonsDisabled = false;
                printMessage('Intener Fehler. Schema-Cache konnte nicht geleert werden!', true);
            });
        }

        function generateToken(){

            TokenService.generate().then(
                function successCallback(response) {
                    vm.token = response.data.message;
                    checkTokenSubmittable();
                },
                function errorCallback(response) {
                    if(response.status == 401){
                        var modalInstance = $uibModal.open({
                            templateUrl: '/ui/default/views/partials/relogin.html?v=' + APP_VERSION,
                            controller: 'ReloginCtrl as vm',
                            backdrop: 'static'
                        });

                        modalInstance.result.then(
                            function () {
                                vm.delete(object);
                            },
                            function () {
                                $uibModalInstance.close();
                                $location.path('/logout');
                            }
                        );

                    }else{
                        var modalInstance = $uibModal.open({
                            templateUrl: '/ui/default/views/partials/modal.html?v=' + APP_VERSION,
                            controller: 'ModalCtrl as vm',
                            resolve: {
                                title: function(){ return 'Fehler beim Löschen'; },
                                body: function(){ return response.data.message; },
                                hideCancelButton: true
                            }
                        });
                    }
                }
            );
        }

        function init(){
            var data = {
                entity: 'PIM\\User'
            };

            EntityService.list(data).then(
                function(response){
                    for(var index in response.data.data){
                        vm.users.push({
                           id: response.data.data[index].id,
                           name: response.data.data[index].alias
                        });
                    }
                    vm.tokenUser        = vm.users[0].id;
                    vm.buttonsDisabled  = false;
                },
                function(response){
                    if(response.status == 401){
                        var modalInstance = $uibModal.open({
                            templateUrl: '/ui/default/views/partials/relogin.html?v=' + APP_VERSION,
                            controller: 'ReloginCtrl as vm',
                            backdrop: 'static'
                        });

                        modalInstance.result.then(
                            function () {
                                init();
                            },
                            function () {
                                $uibModalInstance.close();
                                $location.path('/logout');
                            }
                        );
                    }else{
                      vm.buttonsDisabled  = false;
                    }
                }
            )

            TokenService.list(data).then(
                function(response){
                    vm.tokens = response.data.message;
                },
                function(response){
                    if(response.status == 401){
                        var modalInstance = $uibModal.open({
                            templateUrl: '/ui/default/views/partials/relogin.html?v=' + APP_VERSION,
                            controller: 'ReloginCtrl as vm',
                            backdrop: 'static'
                        });

                        modalInstance.result.then(
                            function () {
                                init();
                            },
                            function () {
                                $uibModalInstance.close();
                                $location.path('/logout');
                            }
                        );
                    }
                }
            );
        }

        function loadSchema(){
            printMessage('Schema wird geladen...', false);
            vm.buttonsDisabled = true;

            $http({
                method: 'GET',
                url: '/api/schema'
            }).then(function successCallback(response) {
                localStorageService.set('schema', response.data.data);
                localStorageService.set('devmode', response.data.devmode);
                localStorageService.set('version', response.data.version);
                localStorageService.set('uiblocks', response.data.uiblocks);
                localStorageService.set('frontend', response.data.frontend);
                localStorageService.set('permissions', response.data.permissions);

                $rootScope.version = localStorageService.get('version');
                $rootScope.devmode = localStorageService.get('devmode');
                $rootScope.frontend = localStorageService.get('frontend');
                $rootScope.schema = localStorageService.get('schema');
                $rootScope.uiblocks = localStorageService.get('uiblocks');
                $rootScope.permissions = localStorageService.get('permissions');

                var entities = {};
                for (var entity in $rootScope.schema) {
                    if(entity == '_hash') continue;
                    if(entity.substr(0, 4) == 'PIM\\' || $rootScope.schema[entity]["settings"]["hide"]) continue;
                    entities[entity] = $rootScope.schema[entity]["settings"]["label"];
                }

                $rootScope.entities = entities;

                printMessage('Schema wurde neu geladen!', true);
                vm.buttonsDisabled = false;

            }, function errorCallback(response) {
                printMessage('Fehler beim Laden des Schemas!', true);
                vm.buttonsDisabled = false;
            });
        }

        function printMessage(message, bold){
            if(!message) return;
            
            if(bold){
                vm.message = '<b>' +  message.replace(new RegExp('\\*', 'g'), '<br>- ') + '</b><br>' + vm.message ;
            }else{
                vm.message = message.replace(new RegExp('\\*', 'g'), '<br>- ') + '<br>' + vm.message ;
            }

        }

        function updateDatabase(){
            vm.buttonsDisabled = true;
            printMessage('Datenbank wird synchronisiert...', false);

            $http({
                method: 'POST',
                url: '/system/do',
                data:{ method: 'updateDatabase'}
            }).then(function successCallback(response) {
                printMessage(response.data.message, true);
                vm.buttonsDisabled = false;
            }, function errorCallback(response) {
                vm.buttonsDisabled = false;
                printMessage('Intener Fehler. Datenbank konnte nicht synchronisiert werden!', true);
            });
        }

        function validateORM(){
            vm.buttonsDisabled = true;
            printMessage('ORM wird validiert...', false);

            $http({
                method: 'POST',
                url: '/system/do',
                data:{ method: 'validateORM'}
            }).then(function successCallback(response) {
                printMessage(response.data.message, true);
                vm.buttonsDisabled = false;
            }, function errorCallback(response) {
                vm.buttonsDisabled = false;
                printMessage('Intener Fehler. ORM konnte nicht validiert werden!', true);
            });
        }
    }

})();

