(function() {
    'use strict';

    angular
        .module('app')
        .factory('FileService', FileService);

    function FileService(localStorageService, $http){

        return{
            overwrite: overwrite
        }


        //////////////////////////////////////////////////////

        function overwrite(sourceId, destId){
            return $http({
                method: 'POST',
                url: 'file/overwrite',
                data: {
                    sourceId: sourceId,
                    destId: destId
                }
            });
        }




    }

})();