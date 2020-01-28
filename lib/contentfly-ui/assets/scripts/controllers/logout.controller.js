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
        localStorageService.remove('permissions');
        localStorageService.remove('i18nPermissions');
        localStorageService.remove('savedFilter');
        localStorageService.remove('uiblocks');
        localStorageService.remove('treeState');
        localStorageService.clearAll();

        $cookies.remove('APPCMS-TOKEN');

        $rootScope.userLoggedIn = false;
        $rootScope.permissions  = null;
        $location.path('/login');
    }

})();

