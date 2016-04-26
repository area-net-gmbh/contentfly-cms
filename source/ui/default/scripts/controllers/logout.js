app.controller("LogoutCtrl", function($scope, $rootScope, $location, localStorageService, $cookies) {
    $scope.login = function() {
        $http({
            method: 'GET',
            url: '/auth/logout'
        }).then(function successCallback(response) {

        }, function errorCallback(response) {

        });
    };

    localStorageService.remove('token');
    localStorageService.remove('schema');
    localStorageService.remove('user');
    localStorageService.remove('version');
    localStorageService.clearAll();
    $rootScope.userLoggedIn = false;
    $location.path('/login');
});