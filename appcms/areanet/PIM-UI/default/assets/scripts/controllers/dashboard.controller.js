(function() {
    'use strict';

    angular
        .module('app')
        .controller('DashboardCtrl', DashboardCtrl);

    function DashboardCtrl($scope, $cookies, localStorageService, $stateParams, $http, $extend){
      var vm  = $extend ? $extend : this;
    }

})();

