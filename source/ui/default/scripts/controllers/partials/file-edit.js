(function() {
    'use strict';

    angular
        .module('app')
        .controller('FileEditCtrl', FileEditCtrl);
    
    function FileEditCtrl($scope, $uibModalInstance, $uibModal, localStorageService, $http, modaltitle, title, id, EntityService){
        var vm = this;

        //Properties
        vm.modaltitle   = modaltitle;
        vm.title        = title;
        vm.id           = id;

        //Functions
        vm.cancel   = cancel;
        vm.ok       = ok;

        /////////////////////

        function cancel(){
            $uibModalInstance.dismiss();
        }

        function ok(){
            var data = {
                entity: "PIM\\File",
                id: vm.id,
                data: {
                    "title": vm.title
                }
            };

            EntityService.update(data).then(
                function successCallback(response) {
                    $uibModalInstance.close(vm.title);
                },
                function errorCallback(response) {
                    alert("SERVERFEHLER");
                }
            );
        }
    }

})();

