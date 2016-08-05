(function() {
    'use strict';

    angular
        .module('app')
        .directive('pimPassword', pimPassword);


    function pimPassword(localStorageService){
        return {
            restrict: 'E',
            scope: {
                key: '=', config: '=', value: '=', object: '=', isValid: '=', isSubmit: '=', onChangeCallback: '&'
            },
            templateUrl: function(){
                return '/ui/default/types/password/password.html'
            },
            link: function(scope, element, attrs){
                scope.writable = parseInt(attrs.writable) > 0;

                scope.newValue = '';
                
                scope.$watch('newValue',function(data){
                    if(scope.newValue){
                        scope.onChangeCallback({key: scope.key, value: scope.newValue});
                    }
                },true)
            }
        }
    }

})();
