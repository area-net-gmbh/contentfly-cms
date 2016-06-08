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

                scope.$watch('value',function(data){
                    scope.onChangeCallback({key: scope.key, value: scope.value});
                },true)
            }
        }
    }

})();
