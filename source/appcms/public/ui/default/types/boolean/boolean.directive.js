(function() {
    'use strict';

    angular
        .module('app')
        .directive('pimBoolean', pimBoolean);


    function pimBoolean(){
        return {
            restrict: 'E',
            scope: {
                key: '=', config: '=', value: '=', isvalid: '=', isSubmit: '=', onChangeCallback: '&'
            },
            templateUrl: function(){
                return 'types/boolean/boolean.html'
            },
            link: function(scope, element, attrs){

                if(scope.value === undefined && scope.config.default != null){
                    scope.value = Boolean(scope.config.default);
                }

                scope.$watch('value',function(data){
                    scope.onChangeCallback({key: scope.key, value: scope.value ? scope.value : false});
                },true)
            }
        }
    }

})();
