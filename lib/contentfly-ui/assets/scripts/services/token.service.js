(function() {
    'use strict';

    angular
        .module('app')
        .factory('TokenService', TokenService);

    function TokenService(localStorageService, $http){

        return{
            delete: doDelete,
            add: add,
            generate: generate,
            list: list
        }


        //////////////////////////////////////////////////////

        function list(id){
            return $http({
                method: 'POST',
                url: 'system/do',
                data: {
                    method: 'listTokens'
                }
            });
        }

        function doDelete(id){
            return $http({
                method: 'POST',
                url: 'system/do',
                data: {
                    method: 'deleteToken',
                    id: id
                }
            });
        }

        function add(referrer, token, user){
            return $http({
                method: 'POST',
                url: 'system/do',
                data: {
                    method: 'addToken',
                    referrer: referrer,
                    user: user,
                    token: token
                }
            });
        }

        function generate(){
            return $http({
                method: 'POST',
                url: 'system/do',
                data: {
                    method: 'generateToken'
                }
            });
        }


    }

})();