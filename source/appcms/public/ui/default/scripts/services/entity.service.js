(function() {
    'use strict';

    angular
        .module('app')
        .factory('EntityService', EntityService);

    function EntityService(localStorageService, $http){

        return{
            delete: doDelete,
            list: list,
            insert: insert,
            update: update,
            multiupdate: multiupdate,
            single: single,
            tree: tree
        }


        //////////////////////////////////////////////////////

        function doDelete(data){
            return $http({
                method: 'POST',
                url: '/api/delete',
                data: data
            });
        }
        
        function list(data){
            return $http({
                method: 'POST',
                url: '/api/list',
                
                data: data
            });
        }

        function insert(data){
            return $http({
                method: 'POST',
                url: '/api/insert',
                
                data: data
            });
        }

        function update(data){
            return $http({
                method: 'POST',
                url: '/api/update',
                data: data
            });
        }

        function multiupdate(data){
            return $http({
                method: 'POST',
                url: '/api/multiupdate',
                data: data
            });
        }

        function single(data){
            return $http({
                method: 'POST',
                url: '/api/single',
                data: data
            });
        }

        function tree(data){
            return $http({
                method: 'POST',
                url: '/api/tree',
                data: data
            });
        }
        
        
    }

})();