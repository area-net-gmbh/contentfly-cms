(function() {
    'use strict';

    angular
        .module('app')
        .directive('pimDatetime', pimDatetime);


    function pimDatetime(localStorageService){
        return {
            restrict: 'E',
            scope: {
                key: '=', config: '=', value: '=', isValid: '=', isSubmit: '=', onChangeCallback: '&'
            },
            templateUrl: function(){
                return '/ui/default/types/datetime/datetime.html?v=' + APP_VERSION
            },
            link: function(scope, element, attrs){

                scope.writable = parseInt(attrs.writable) > 0;

                scope.dateValue = scope.value && scope.value.ISO8601 ? moment(scope.value.ISO8601).toDate() : null;
                scope.isOpened  = false;

                scope.openDatePicker = function(){
                    scope.isOpened = true;
                }

                scope.$watch('value',function(data){
                    if(!scope.writable){
                        return;
                    }

                    scope.dateValue = scope.value ? moment(scope.value.ISO8601).toDate() : null;
                },true);

                scope.$watch('dateValue',function(data){

                  if(!scope.writable){
                        return;
                    }

                    if(scope.dateValue == null || !scope.dateValue.getDate()){
                        scope.onChangeCallback({key: scope.key, value: null});

                      scope.value = {
                        "ISO8601": null,
                        "LOCAL": null,
                        "LOCAL_TIME": null,
                        "TIMESTAMP": null
                      };

                      scope.dateValue = null;
                    }else{
                        var momentJS = moment(scope.dateValue.toISOString());
                        scope.onChangeCallback({key: scope.key, value: momentJS.format('YYYY-MM-DD')});

                        scope.value = {
                          "ISO8601": momentJS.toISOString(),
                          "LOCAL": momentJS.format('DD.MM.YYYY'),
                          "LOCAL_TIME": momentJS.format('DD.MM.YYYY hh:mm'),
                          "TIMESTAMP": momentJS.unix()
                        };
                    }

                },true)
            }
        }
    }

})();
