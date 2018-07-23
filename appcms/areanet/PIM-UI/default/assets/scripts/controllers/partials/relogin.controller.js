(function() {
    'use strict';

    angular
        .module('app')
        .controller('ReloginCtrl', ReloginCtrl);

    function ReloginCtrl($scope, $uibModalInstance, localStorageService, $http, $cookies, $location){
        var vm = this;

        //Properties
        vm.password = null;

        //Functions
        vm.ok       = ok;
        vm.cancel   = cancel;

        ///////////////////////////////////

        function ok() {
            var user = localStorageService.get('user');

            $http({
                method: 'POST',
                url: '/auth/login',
                data: {alias: user.alias, pass: vm.password}
            }).then(function successCallback(response) {
                $cookies.put('APPCMS-TOKEN', response.data.token);

                $http.defaults.headers.common = {
                  'APPCMS-TOKEN': response.data.token
                };

                $uibModalInstance.close(true);
            }, function errorCallback(response) {
                vm.error = response.data.message;
            });
            
            
        }

        function cancel() {
            $uibModalInstance.dismiss(false);
            $location.path('/logout')
        }
    }

})();