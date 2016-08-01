(function() {
    'use strict';

    angular
        .module('app')
        .controller('LogoutCtrl', LogoutCtrl);

    function LogoutCtrl($scope, $rootScope, $location, localStorageService, $cookies){
        
        localStorageService.remove('token');
        localStorageService.remove('schema');
        localStorageService.remove('frontend');
        localStorageService.remove('user');
        localStorageService.remove('version');
        localStorageService.remove('savedFilter');
        localStorageService.clearAll();
        $rootScope.userLoggedIn = false;
        $location.path('/login');
    }

})();

