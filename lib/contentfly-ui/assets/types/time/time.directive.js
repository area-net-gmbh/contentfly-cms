(function() {
    'use strict';

    angular
        .module('app')
        .directive('pimTime', pimTime);

    function pimTime(localStorageService){
        return {
            restrict: 'E',
            scope: {
                key: '=', config: '=', value: '=', isvalid: '=', isSubmit: '=', onChangeCallback: '&'
            },
            templateUrl: function(){
                return 'lib/contentfly-ui/assets/types/time/time.html?v=' + APP_VERSION
            },
            link: function(scope, element, attrs){
                scope.writable = parseInt(attrs.writable) > 0;

                scope.timeValue = scope.value ? new moment(scope.value, 'HH:mm') : null;

                scope.$watch('value',function(data){
                    if(!scope.writable){
                        return;
                    }
                    scope.timeValue = scope.value ? new moment(scope.value, 'HH:mm') : null;
                },true)

                scope.$watch('timeValue',function(data){
                    if(!scope.writable){
                        return;
                    }

                    if(scope.timeValue == null){
                        scope.onChangeCallback({key: scope.key, value: null});
                    }else{
                        var momentJS = moment(scope.timeValue.toISOString());
                        scope.onChangeCallback({key: scope.key, value: momentJS.format('HH:mm')});
                    }                    
                },true)
            }
        }
    }

})();