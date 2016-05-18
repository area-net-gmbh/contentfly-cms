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
                return 'types/datetime/datetime.html'
            },
            link: function(scope, element, attrs){
                //object[key] ? moment(object[key].ISO8601).toDate() : new Date();
                scope.dateValue = scope.value ? moment(scope.value.ISO8601).toDate() : null;
                scope.isOpened  = false;

                scope.openDatePicker = function(){
                    scope.isOpened = true;
                }

                scope.$watch('dateValue',function(data){
                    scope.onChangeCallback({key: scope.key, value: scope.dateValue ? scope.dateValue.toISOString() : null});
                },true)
            }
        }
    }

})();
