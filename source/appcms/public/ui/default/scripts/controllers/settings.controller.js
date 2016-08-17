(function() {
    'use strict';

    angular
        .module('app')
        .controller('SettingsCtrl', SettingsCtrl);

    function SettingsCtrl($scope, $cookies, localStorageService, $routeParams, $http, $rootScope){
        var vm = this;

        //Properties
        vm.schemaMessage = '';
        vm.schemaButtonDisabled = false;

        //Functions
        vm.loadSchema           = loadSchema;

        ///////////////////////////////////

        function loadSchema(){
            vm.schemaMessage = 'Schema wird geladen...';
            vm.schemaButtonDisabled = true;

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

                vm.schemaMessage = 'Schema wurde neu geladen!';
                vm.schemaButtonDisabled = false;

            }, function errorCallback(response) {
                vm.schemaMessage = 'Fehler beim Laden des Schemas!';
                vm.schemaButtonDisabled = false;
            });
        }
    }

})();

