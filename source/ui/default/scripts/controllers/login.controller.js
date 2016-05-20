(function() {
    'use strict';

    angular
        .module('app')
        .controller('LoginCtrl', LoginCtrl);

    function LoginCtrl($scope, $location, localStorageService, $cookies, $http){
        var vm = this;

        vm.login  = login;
        vm.schema = schema;

        //////////////////////////////

        function login(){
            $http({
                method: 'POST',
                url: '/auth/login',
                data: {alias: vm.alias, pass: vm.password}
            }).then(function successCallback(response) {
                localStorageService.set('token', response.data.token);
                localStorageService.set('user', response.data.user);
                schema();
            }, function errorCallback(response) {
                vm.error = response.data.message;
            });
        }

        function schema(){
            $http({
                method: 'GET',
                url: '/api/schema',
                headers: { 'X-Token': localStorageService.get('token') },
            }).then(function successCallback(response) {
                localStorageService.set('schema', response.data.data);
                localStorageService.set('version', response.data.version);
                localStorageService.set('devmode', response.data.devmode);
                localStorageService.set('frontend', response.data.frontend);
                $location.path("/");
            }, function errorCallback(response) {
                vm.error = response.data.message;
            });

        }
    }

})();
