(function() {
    'use strict';

    angular
        .module('app')
        .directive('pimInteger', pimInteger);


    function pimInteger(){
        return {
            restrict: 'E',
            scope: {
                key: '=', config: '=', value: '=', isValid: '=', isSubmit: '=', onChangeCallback: '&'
            },
            templateUrl: function(){
                return 'types/integer/integer.html'
            },
            link: function(scope, element, attrs){
                if(scope.value === undefined && scope.config.default != null){
                    scope.value = parseInt(scope.config.default);
                }

                scope.$watch('value',function(data){
                    scope.onChangeCallback({key: scope.key, value: scope.value});
                },true)
            }
        }
    }

})();
