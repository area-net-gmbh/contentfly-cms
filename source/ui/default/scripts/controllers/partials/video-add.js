app.controller('VideoAddCtrl', function($scope, $uibModalInstance, $uibModal, localStorageService, $http, modaltitle, property) {
    $scope.modaltitle = modaltitle;
    $scope.youtubeId = "";
    $scope.title = "";

    $scope.ok = function () {

        if(id == ""){
            $uibModalInstance.dismiss();
            return;
        }

        $http({
            method: 'POST',
            url: '/api/insert',
            headers: {'X-Token': localStorageService.get('token')},
            data: {
                entity: "PIM\\File",
                data: {
                    "title": $scope.title,
                    "name": $scope.youtubeId,
                    "type": "link/youtube",
                    "hash": $scope.youtubeId,
                    "size": 0
                }
            }
        }).then(function successCallback(response) {
            var fileData = JSON.parse(response.data.data);
            $uibModalInstance.close(fileData);
        }, function errorCallback(response) {
            alert("SERVERFEHLER");
        });
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };
});