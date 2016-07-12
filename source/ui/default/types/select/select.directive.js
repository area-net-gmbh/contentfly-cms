(function() {
    'use strict';

    angular
        .module('app')
        .directive('pimSelect', pimSelect);


    function pimSelect(){
        return {
            restrict: 'E',
            scope: {
                key: '=', config: '=', value: '=', isValid: '=', isSubmit: '=', onChangeCallback: '&'
            },
            templateUrl: function(){
                return 'types/select/select.html'
            },
            link: function(scope, element, attrs){
                if((scope.value === undefined || scope.value == null) && scope.config.default != null){
                    scope.value = (scope.config.default);
                }
                
                if(parseInt(scope.value)){
                    scope.value = scope.value.toString();
                }
                

                scope.$watch('value',function(data){
                    if(parseInt(scope.value)){
                        scope.value = scope.value.toString();
                    }
                    scope.newValue = scope.value;
                },true)

                scope.$watch('newValue',function(data){
                    scope.onChangeCallback({key: scope.key, value: scope.newValue});
                },true)
            }
        }
    }

})();
