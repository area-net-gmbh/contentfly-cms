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
                return 'types/textarea/textarea.html'
            },
            link: function(scope, element, attrs){
                scope.$watch('value',function(data){
                    scope.onChangeCallback({key: scope.key, value: scope.value});
                },true)
            }
        }
    }

})();
