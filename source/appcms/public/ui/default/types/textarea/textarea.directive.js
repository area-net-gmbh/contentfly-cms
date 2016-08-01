(function() {
    'use strict';

    angular
        .module('app')
        .directive('pimTextarea', pimTextarea);


    function pimTextarea(){
        return {
            restrict: 'E',
            scope: {
                key: '=', config: '=', value: '=', isValid: '=', isSubmit: '=', onChangeCallback: '&'
            },
            templateUrl: function(){
                return '/ui/default/types/textarea/textarea.html'
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
