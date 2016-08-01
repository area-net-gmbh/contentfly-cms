(function() {
    'use strict';

    angular
        .module('app')
        .directive('pimString', pimString);


    function pimString(){
        return {
            restrict: 'E',
            scope: {
                key: '=', config: '=', value: '=', isValid: '=', isSubmit: '=', onChangeCallback: '&'
            },
            templateUrl: function(){
                return '/ui/default/types/string/string.html'
            },
            link: function(scope, element, attrs){
                if(scope.value === undefined && scope.config.default != null){
                    scope.value = (scope.config.default);
                }

                scope.$watch('value',function(data){
                    scope.onChangeCallback({key: scope.key, value: scope.value});
                },true)
            }
        }
    }

})();
