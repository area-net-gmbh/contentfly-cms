(function() {
    'use strict';

    angular
        .module('app')
        .controller('ErrorCtrl', ErrorCtrl);

    function ErrorCtrl($scope, $cookies, localStorageService, $stateParams, $rootScope, $http, $extend){
      var vm  = $extend ? $extend : this;

        vm.msg = $rootScope.error;

    }

})();

