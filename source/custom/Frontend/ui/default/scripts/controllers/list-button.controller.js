(function() {
    'use strict';

    angular
        .module('app')
        .controller('ListButtonCtrl', ListButtonCtrl);

    function ListButtonCtrl($scope, $rootScope, $location, localStorageService, $cookies){

        var vm  = this;

        vm.click = click;

        function click(object){
            console.log(object);
        }
    }

})();

