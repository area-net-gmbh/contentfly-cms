(function() {
    'use strict';

    angular
        .module('app')
        .config(routeConfiguration);

    function routeConfiguration($routeProvider){
        $routeProvider
            .when('/', {
                templateUrl: 'views/dashboard.html',
                controller: 'DashboardCtrl as vm',
                secure: true
            })
            .when('/about', { template: 'Über unsere Pizzeria', secure: true })
            .when('/login', {
                templateUrl: 'views/login.html',
                controller: 'LoginCtrl as vm'
            })
            .when('/logout', {
                templateUrl: 'views/login.html',
                controller: 'LogoutCtrl as vm'
            })
            .when('/list/:entity', {
                templateUrl: 'views/list.html',
                controller: 'ListCtrl as vm',
                resolve: { pimEntity: function(){return null;} },
                secure: true
            })
            .when('/list/PIM/:entity', {
                templateUrl: 'views/list.html',
                controller: 'ListCtrl as vm',
                resolve: { pimEntity: function(){return true;} },
                secure: true
            })
            .when('/files', {
                templateUrl: 'views/files.html',
                controller: 'FilesCtrl as vm',
                resolve: {
                    pimEntity: function(){return true;},
                    modaltitle: function(){return null;},
                    property: function(){return null;},
                    '$uibModalInstance': function(){return null;}
                },
                secure: true
            })
            .otherwise({ redirectTo: '/' });
    }
})();