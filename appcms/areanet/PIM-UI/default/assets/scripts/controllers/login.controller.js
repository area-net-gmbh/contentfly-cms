(function() {
    'use strict';

    angular
        .module('app')
        .controller('LoginCtrl', LoginCtrl);

    function LoginCtrl($scope, $location, localStorageService, $cookies, $rootScope, $http){
        var vm = this;

        //Properties
        vm.config               = null;
        vm.logoIsInitialisied   = false;
        vm.canLogin             = false;

        //Functions
        vm.change   = change;
        vm.login    = login;
        vm.schema   = schema;

        //Startup
        init();

        //////////////////////////////

        function change(){
            if(vm.alias && vm.password){
                vm.canLogin = true;
            }else{
                vm.canLogin = false;
            }
        }

        function init(){
            $http({
                method: 'GET',
                url: '/api/config'
            }).then(function successCallback(response) {
                vm.config = response.data;
                vm.logoIsInitialisied = true;
                $rootScope.uiblocks = response.data.uiblocks;
            }, function errorCallback(response) {
            });
        }

        function login(){
            $http({
                method: 'POST',
                url: '/auth/login',
                data: {alias: vm.alias, pass: vm.password}
            }).then(function successCallback(response) {
                localStorageService.set('user', response.data.user);
                $cookies.put('APPCMS-TOKEN', response.data.token);

                schema(response.data.token);
            }, function errorCallback(response) {
                vm.error = response.data.message;
            });
        }

        function schema(token){

            $http({
                method: 'GET',
                url: '/api/schema',
                headers: {
                    'APPCMS-TOKEN': token
                }
            }).then(function successCallback(response) {
                localStorageService.set('schema', response.data.data);
                localStorageService.set('version', response.data.version);
                localStorageService.set('devmode', response.data.devmode);
                localStorageService.set('frontend', response.data.frontend);
                localStorageService.set('permissions', response.data.permissions);
                localStorageService.set('uiblocks', response.data.uiblocks);
                $location.path(response.data.frontend.login_redirect);
            }, function errorCallback(response) {
                vm.error = response.data.message;
            });

        }
    }

})();
