(function() {
    'use strict';

    angular
        .module('app')
        .directive('pimRte', pimRte);


    function pimRte(localStorageService){
        return {
            restrict: 'E',
            scope: {
                key: '=', config: '=', value: '=', isValid: '=', isSubmit: '=', onChangeCallback: '&'
            },
            templateUrl: function(){
                return '/ui/default/types/rte/rte.html?v=' + APP_VERSION
            },
            link: function(scope, element, attrs){
                //scope.disabled = !parseInt(attrs.writable);
                //console.log(scope.key + ":: " + scope.config.readonly + " = " + attrs.writable + " == " + scope.disabled);
                scope.writable = parseInt(attrs.writable) > 0;

                if(scope.value === undefined && scope.config.default != null){
                    scope.value     = (scope.config.default);
                }
                
                scope.$watch('value',function(data){

                    if(!scope.writable){
                        return;
                    }

                    scope.onChangeCallback({key: scope.key, value: scope.value});
                    scope.disabled  = !parseInt(attrs.writable);

                },true)
            }
        }
    }

})();
