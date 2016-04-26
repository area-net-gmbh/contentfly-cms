app.controller("ModalCtrl", function($scope, $uibModalInstance, title, body, hideCancelButton) {

    $scope.title = title;
    $scope.body = body;
    $scope.hideCancelButton = hideCancelButton;

    $scope.ok = function () {
        $uibModalInstance.close(true);
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss(false);
    };
});