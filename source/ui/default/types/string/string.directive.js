(function() {
    'use strict';

    angular
        .module('app')
        .directive('pimString', pimString);


    function pimString(){
        return {
            restrict: 'E',
            scope: {
                key: '=', config: '=', value: '=', isvalid: '=', submitted: '=', onChangeCallback: '&'
            },
            templateUrl: function(){
                return 'types/string/string.html'
            },
            link: function(scope, element, attrs){
                scope.$watch('value',function(data){
                    scope.onChangeCallback({key: scope.key, value: scope.value});
                },true)
            }
        }
    }

})();
