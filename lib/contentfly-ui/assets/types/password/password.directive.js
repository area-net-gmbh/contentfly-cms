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
                return 'lib/contentfly-ui/assets/types/password/password.html?v=' + APP_VERSION
            },
            link: function(scope, element, attrs){
                scope.writable = parseInt(attrs.writable) > 0;

                scope.newValue = '';
                
                scope.$watch('newValue',function(data){
                    if(!scope.writable){
                        return;
                    }

                    if(scope.newValue){
                        scope.onChangeCallback({key: scope.key, value: scope.newValue});
                    }
                },true)
            }
        }
    }

})();
