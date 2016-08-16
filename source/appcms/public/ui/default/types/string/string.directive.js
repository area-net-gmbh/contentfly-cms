(function() {
    'use strict';

    angular
        .module('app')
        .directive('pimString', pimString);


    function pimString(localStorageService){
        return {
            restrict: 'E',
            scope: {
                key: '=', config: '=', value: '=', isValid: '=', isSubmit: '=', onChangeCallback: '&'
            },
            templateUrl: function(){
                return '/ui/default/types/string/string.html'
            },
            link: function(scope, element, attrs){

                scope.writable = parseInt(attrs.writable) > 0;
                
                if(scope.value === undefined && scope.config.default != null){
                    scope.value = (scope.config.default);
                }

                scope.$watch('value',function(data){
                    if(!scope.writable){
                        return;
                    }
                    
                    scope.onChangeCallback({key: scope.key, value: scope.value});
                },true)
            }
        }
    }

})();
