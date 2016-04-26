app.controller('EntityBrowserCtrl', function($scope, $uibModalInstance, $uibModal, title, schema, selectedObject) {
    $scope.modaltitle = title;

    $scope.selectedObject = selectedObject;

    var entities = {};
    for (entity in schema) {
        if(entity.substr(0, 4) == 'PIM\\') continue;
        entities[entity] = schema[entity]["settings"]["label"];
    }
    $scope.objects = entities;
    $scope.selectObject = function(entity, label) {
        $uibModalInstance.close(entity, label);
    };
});