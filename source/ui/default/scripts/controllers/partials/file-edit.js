app.controller('FileEditCtrl', function($scope, $uibModalInstance, $uibModal, localStorageService, $http, modaltitle, title, id) {
    $scope.modaltitle = modaltitle;
    $scope.title = title;
    $scope.id = id;

    $scope.ok = function () {

        $http({
            method: 'POST',
            url: '/api/update',
            headers: {'X-Token': localStorageService.get('token')},
            data: {
                entity: "PIM\\File",
                id: id,
                data: {
                    "title": $scope.title
                }
            }
        }).then(function successCallback(response) {
            $uibModalInstance.close($scope.title);
        }, function errorCallback(response) {
            alert("SERVERFEHLER");
        });
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };
});