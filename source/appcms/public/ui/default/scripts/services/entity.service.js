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
                headers: { 'X-Token': localStorageService.get('token') },
                data: data
            });
        }
        
        function list(data){
            return $http({
                method: 'POST',
                url: '/api/list',
                headers: { 'X-Token': localStorageService.get('token') },
                data: data
            });
        }

        function insert(data){
            return $http({
                method: 'POST',
                url: '/api/insert',
                headers: {'X-Token': localStorageService.get('token')},
                data: data
            });
        }

        function update(data){
            return $http({
                method: 'POST',
                url: '/api/update',
                headers: {'X-Token': localStorageService.get('token')},
                data: data
            });
        }

        function multiupdate(data){
            return $http({
                method: 'POST',
                url: '/api/multiupdate',
                headers: {'X-Token': localStorageService.get('token')},
                data: data
            });
        }

        function single(data){
            return $http({
                method: 'POST',
                url: '/api/single',
                headers: { 'X-Token': localStorageService.get('token') },
                data: data
            });
        }

        function tree(data){
            return $http({
                method: 'POST',
                url: '/api/tree',
                headers: { 'X-Token': localStorageService.get('token') },
                data: data
            });
        }
        
        
    }

})();