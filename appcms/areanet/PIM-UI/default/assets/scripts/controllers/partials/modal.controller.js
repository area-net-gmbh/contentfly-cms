(function() {
    'use strict';

    angular
        .module('app')
        .controller('ModalCtrl', ModalCtrl);

    function ModalCtrl($scope, $uibModalInstance, title, body, hideCancelButton){
        var vm = this;

        //Properties
        vm.title            = title;
        vm.body             = body;
        vm.hideCancelButton = hideCancelButton;
        
        //Functions
        vm.ok       = ok;
        vm.cancel   = cancel;

        ///////////////////////////////////

        function ok() {
            $uibModalInstance.close(true);
        }

        function cancel() {
            $uibModalInstance.dismiss(false);
        }
    }

})();