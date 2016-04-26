app.controller('ObjectBrowserCtrl', function($scope, $uibModalInstance, $uibModal, title, schema, entityToSelect, $http, localStorageService) {
    $scope.modaltitle = title;
    $scope.objectsAvailable = false;
    $scope.objectsNotAvailable = false;

    var entities = {};

    for(entity in schema) {
        if(entityToSelect){
            if(entity.substr(0, 4) == 'PIM\\' && entity != entityToSelect) continue;
            if(entity.substr(0, 4) != 'PIM\\' && 'Custom\\Entity\\' + entity != entityToSelect) continue;
        }else{
            if(entity.substr(0, 4) == 'PIM\\') continue;
        }

        entities[entity] = schema[entity]["settings"]["label"];
    }

    $scope.entities = entities;

    /**
     * Select object from list
     */
    $scope.selectObject = function(entity, objectToSelect) {


        $uibModalInstance.close(objectToSelect);
    };

    /**
     * Load entity objects
     */
    $scope.loadEntityObjects = function(entity) {
        $http({
            method: 'POST',
            url: '/api/list',
            headers: { 'X-Token': localStorageService.get('token') },
            data: {entity: entity}
        }).then(function(response) {
            $scope.objectsAvailable = true;
            $scope.objectsNotAvailable = false;
            $scope.objects = JSON.parse(response.data.data);
        }, function(response) {
            $scope.objectsAvailable = false;
            $scope.objectsNotAvailable = true;
        });
    };
});