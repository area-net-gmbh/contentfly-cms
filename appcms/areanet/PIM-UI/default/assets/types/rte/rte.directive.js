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
                scope.disabled = !parseInt(attrs.writable) || scope.config.readonly;
                if(scope.disabled){
                    element.find('[contenteditable]').removeAttr('contenteditable');
                }

                if(scope.value === undefined && scope.config.default != null){
                    scope.value     = (scope.config.default);
                }

                scope.$watch('value',function(data){
                    scope.disabled  = !parseInt(attrs.writable) || scope.config.readonly;
                    if(scope.disabled){
                        element.find('[contenteditable]').removeAttr('contenteditable');
                    }

                    
                    if(scope.disabled){
                        return;
                    }

                    scope.onChangeCallback({key: scope.key, value: scope.value});
                   
                },true)
            }
        }
    }

})();
