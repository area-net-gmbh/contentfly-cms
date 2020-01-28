(function() {
    'use strict';

    angular
        .module('app')
        .factory('EntityService', EntityService);

    function EntityService(localStorageService, $http){

        return{
            delete: doDelete,
            exportData: exportData,
            list: list,
            insert: insert,
            update: update,
            multiupdate: multiupdate,
            single: single,
            translations: translations,
            tree: tree,
            tree2: tree2
        };


        //////////////////////////////////////////////////////

        function doDelete(data){
            return $http({
                method: 'POST',
                url: '/api/delete',
                data: data
            });
        }

        function exportData(type, data){
          return $http({
            method: 'POST',
            url: '/export/' + type,
            data: data,
            responseType: 'blob'
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

        function translations(entity, lang){
            return $http({
                method: 'POST',
                url: '/api/translations',
                data: {
                  entity: entity,
                  lang: lang
                }
            });
        }

        function tree(data){
          return $http({
            method: 'POST',
            url: '/api/tree',
            data: data
          });
        }

      function tree2(data){
        return $http({
          method: 'POST',
          url: '/api/tree2',
          data: data
        });
      }
        
        
    }

})();