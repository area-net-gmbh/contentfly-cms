(function() {
    'use strict';

    angular
        .module('app')
        .run(run);

    function run($rootScope, $location, $cookies, localStorageService){
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
                    for (var entity in $rootScope.schema) {
                        if(entity.substr(0, 4) == 'PIM\\' || $rootScope.schema[entity]["settings"]["hide"]) continue;
                        entities[entity] = $rootScope.schema[entity]["settings"]["label"];
                    }

                    $rootScope.entities = entities;

                }
            }else{
                $rootScope.frontend = localStorageService.get('frontend');
            }
        });
    }

})();
