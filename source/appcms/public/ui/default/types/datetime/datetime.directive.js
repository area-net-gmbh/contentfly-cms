(function() {
    'use strict';

    angular
        .module('app')
        .directive('pimDatetime', pimDatetime);


    function pimDatetime(){
        return {
            restrict: 'E',
            scope: {
                key: '=', config: '=', value: '=', isValid: '=', isSubmit: '=', onChangeCallback: '&'
            },
            templateUrl: function(){
                return '/ui/default/types/datetime/datetime.html'
            },
            link: function(scope, element, attrs){
                //object[key] ? moment(object[key].ISO8601).toDate() : new Date();
                scope.dateValue = scope.value ? moment(scope.value.ISO8601).toDate() : null;
                scope.isOpened  = false;

                scope.openDatePicker = function(){
                    scope.isOpened = true;
                }

                scope.$watch('value',function(data){
                    scope.dateValue = scope.value ? moment(scope.value.ISO8601).toDate() : null;
                },true)

                scope.$watch('dateValue',function(data){
                    if(scope.dateValue == null){
                        scope.onChangeCallback({key: scope.key, value: null});
                    }else{
                        var momentJS = moment(scope.dateValue.toISOString());
                        scope.onChangeCallback({key: scope.key, value: momentJS.format('YYYY-MM-DD')});
                    }

                },true)
            }
        }
    }

})();
