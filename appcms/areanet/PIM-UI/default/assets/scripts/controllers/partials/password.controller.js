(function() {
    'use strict';

    angular
        .module('app')
        .controller('PasswordCtrl', PasswordCtrl);

    function PasswordCtrl($scope, $rootScope, $location, localStorageService, $cookies, $uibModalInstance, EntityService, object) {

        var vm = this;

        //Properties
        vm.buttonsDisabled      = false;
        vm.doSave               = false;
        vm.isAdmin              = object.isAdmin;
        vm.isSubmitted          = false;
        vm.object               = object;
        vm.passwordCurrent      = null;
        vm.passwordNew          = null;
        vm.passwordNewRepeat    = null;

        //Functions
        vm.cancel   = cancel;
        vm.save     = save;

        //Startup
        init();

        ///////////////////////////////////

        function cancel(){
            $uibModalInstance.dismiss(false);
        }

        function init(){

        }

        function save(){
            vm.isSubmitted = true;

            if(!object.isAdmin) $scope.passwordForm.passwordCurrent.$setValidity("match", true);
            $scope.passwordForm.passwordNewRepeat.$setValidity("permission", true);

            if(vm.passwordNew != vm.passwordNewRepeat){
                $scope.passwordForm.passwordNewRepeat.$setValidity("required", false);
            }

            if($scope.passwordForm.$invalid){
                return;
            }

            vm.doSave = true;

            var data = {
                entity: 'PIM\\User',
                id: object.id,
                data: {
                    pass: vm.passwordNew
                }
            };

            if(!object.isAdmi){
              data.pass =  vm.passwordCurrent;
            }

            EntityService.update(data).then(
                function successCallback(response) {
                    $uibModalInstance.close();
                },
                function errorCallback(response) {

                    console.log(response.status);

                    if(response.status == 401){
                        var modalInstance = $uibModal.open({
                            templateUrl: '/ui/default/views/partials/relogin.html?v=' + APP_VERSION,
                            controller: 'ReloginCtrl as vm',
                            backdrop: 'static'
                        });

                        modalInstance.result.then(
                            function () {
                                save();
                            },
                            function () {
                                $uibModalInstance.close();
                                $location.path('/logout');
                            }
                        );

                    }else if(response.status == 403){
                      vm.doSave = false;
                      $scope.passwordForm.passwordNewRepeat.$setValidity("permission", false);
                    }else {
                        vm.doSave = false;
                        if(!vm.isAdmin) $scope.passwordForm.passwordCurrent.$setValidity("match", false);
                    }
                }
            );
        }

    }

})();