(function() {
    'use strict';

    angular
        .module('app')
        .controller('VideoAddCtrl', VideoAddCtrl);

    function VideoAddCtrl($scope, $uibModalInstance, $uibModal, localStorageService, $http, modaltitle, property, EntityService){
        var vm = this;

        //Properties
        vm.modaltitle   = modaltitle;
        vm.youtubeId    = "";
        vm.title        = "";

        //Functions
        vm.cancel   = cancel;
        vm.ok       = ok;

        ////////////////////////

        function cancel () {
            $uibModalInstance.dismiss();
        }

        function ok () {

            if(id == ""){
                $uibModalInstance.dismiss();
                return;
            }

            var data = {
                entity: "PIM\\File",
                data: {
                    "title": vm.title,
                    "name":  vm.youtubeId,
                    "type":  "link/youtube",
                    "hash":  vm.youtubeId,
                    "size":  0
                }
            };

            EntityService.insert(data).then(
                function successCallback(response) {
                    var fileData = response.data.data;
                    $uibModalInstance.close(fileData);
                },
                function errorCallback(response) {
                    alert("SERVERFEHLER");
                }
            );


        }
    }

})();
