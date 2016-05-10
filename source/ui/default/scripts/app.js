var app = angular.module('BackendUI', [ 'ui.bootstrap', 'ngRoute', 'ngCookies', 'ngFileUpload', 'textAngular', 'LocalStorageModule', 'angularMoment', 'angularUtils.directives.dirPagination', 'angularGrid', 'chart.js']);



app.config(function($routeProvider) {
    $routeProvider
        .when('/', {
            templateUrl: 'views/dashboard.html',
            controller: 'DashboardCtrl',
            secure: true
        })
        .when('/about', { template: 'Über unsere Pizzeria', secure: true })
        .when('/login', {
            templateUrl: 'views/login.html',
            controller: 'LoginCtrl'
        })
        .when('/logout', {
            templateUrl: 'views/login.html',
            controller: 'LogoutCtrl'
        })
        .when('/list/:entity', {
            templateUrl: 'views/list.html',
            controller: 'ListCtrl',
            resolve: { pimEntity: function(){return false;} },
            secure: true
        })
        .when('/list/PIM/:entity', {
            templateUrl: 'views/list.html',
            controller: 'ListCtrl',
            resolve: { pimEntity: function(){return true;} },
            secure: true
        })
        .when('/files', {
            templateUrl: 'views/files.html',
            controller: 'FilesCtrl',
            resolve: {
                pimEntity: function(){return true;},
                modaltitle: function(){return null;},
                property: function(){return null;},
                '$uibModalInstance': function(){return null;}
            },
            secure: true
        })
        .otherwise({ redirectTo: '/' });
});

app.run(function($rootScope, $location, $cookies, localStorageService) {

    $rootScope.$on( "$routeChangeStart", function(event, next, current) {
        if(next.secure){
            //localStorageService.set('localStorageKey','Add this!');
            if (localStorageService.get('token') == null) {
                $location.path("/login");
            }else{
                $rootScope.userLoggedIn = true;
                $rootScope.user = localStorageService.get('user');
                $rootScope.version = localStorageService.get('version');
                $rootScope.token = localStorageService.get('token');
                $rootScope.devmode = localStorageService.get('devmode');
                $rootScope.frontend = localStorageService.get('frontend');
                $rootScope.schema = localStorageService.get('schema');

                var entities = {};
                for (entity in $rootScope.schema) {
                    if(entity.substr(0, 4) == 'PIM\\') continue;
                    entities[entity] = $rootScope.schema[entity]["settings"]["label"];
                }

                $rootScope.entities = entities;

            }
        }
    });
});