(function() {
    'use strict';

    angular
        .module('app')
        .directive('pimTest', pimTest);


    function pimTest(localStorageService){
        return {
            restrict: 'E',
            scope: {
                key: '=', config: '=', value: '=', isvalid: '=', isSubmit: '=', onChangeCallback: '&'
            },
            templateUrl: function(){
                return '/custom/Frontend/ui/default/types/test/test.html'
            },
            link: function(scope, element, attrs){
                scope.writable = parseInt(attrs.writable) > 0;

                if(scope.value === undefined && scope.config.default != null){
                    scope.value = Boolean(scope.config.default);
                }

                scope.$watch('value',function(data){

                    scope.value = Boolean(scope.value);
                    if(scope.writable) scope.onChangeCallback({key: scope.key, value: scope.value ? scope.value : false});
                },true)
            }
        }
    }

})();
