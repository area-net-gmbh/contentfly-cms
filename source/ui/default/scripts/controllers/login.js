app.controller("LoginCtrl", function($scope, $location, localStorageService, $cookies, $http) {

    $scope.login = function() {
        $http({
            method: 'POST',
            url: '/auth/login',
            data: {alias: $scope.alias, pass: $scope.password}
        }).then(function successCallback(response) {
            localStorageService.set('token', response.data.token);
            localStorageService.set('user', response.data.user);
            $scope.schema();
        }, function errorCallback(response) {
            $scope.error = response.data.message;
        });
    };

    $scope.schema = function(){
        $http({
            method: 'GET',
            url: '/api/schema',
            headers: { 'X-Token': localStorageService.get('token') },
        }).then(function successCallback(response) {
            localStorageService.set('schema', response.data.data);
            localStorageService.set('version', response.data.version);
            localStorageService.set('devmode', response.data.devmode);
            $location.path("/");
        }, function errorCallback(response) {
            $scope.error = response.data.message;
        });

    }
});