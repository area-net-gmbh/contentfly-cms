(function() {
    'use strict';

    angular
        .module('app')
        .controller('SettingsCtrl', SettingsCtrl);

    function SettingsCtrl($scope, $cookies, localStorageService, $routeParams, $http, $rootScope){
        var vm = this;

        //Properties
        vm.message          =  '';
        vm.buttonsDisabled  = false;

        //Functions
        vm.flushSchemaCache     = flushSchemaCache;
        vm.loadSchema           = loadSchema;
        vm.updateDatabase       = updateDatabase;

        ///////////////////////////////////

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
            if(bold){
                vm.message = '<b>' + message + '</b><br>' + vm.message ;
            }else{
                vm.message = message + '<br>' + vm.message ;
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
    }

})();

