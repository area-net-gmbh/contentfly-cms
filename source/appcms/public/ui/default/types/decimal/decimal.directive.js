(function() {
    'use strict';

    angular
        .module('app')
        .directive('pimDecimal', pimDecimal);


    function pimDecimal(localStorageService){
        return {
            restrict: 'E',
            scope: {
                key: '=', config: '=', value: '=', isValid: '=', isSubmit: '=', onChangeCallback: '&'
            },
            templateUrl: function(){
                return '/ui/default/types/decimal/decimal.html'
            },
            link: function(scope, element, attrs){
                scope.writable = parseInt(attrs.writable) > 0;

                if(scope.value === undefined && scope.config.default != null){
                    scope.value = parseFloat(scope.config.default).toFixed(2);
                }

                scope.value = scope.value ? scope.value.replace('.', ',') : null;

                scope.$watch('value',function(data){
                    if(!scope.writable){
                        return;
                    }
                    
                    scope.onChangeCallback({key: scope.key, value: scope.value ? scope.value.replace(',', '.') : null});
                },true)
            }
        }
    }

})();
