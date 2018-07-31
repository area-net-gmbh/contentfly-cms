(function() {
    'use strict';

    angular
        .module('app')
        .controller('ErrorCtrl', ErrorCtrl);

    function ErrorCtrl($scope, $cookies, localStorageService, $routeParams, $rootScope, $http){
        var vm = this;

        vm.msg = $rootScope.error;

    }

})();

