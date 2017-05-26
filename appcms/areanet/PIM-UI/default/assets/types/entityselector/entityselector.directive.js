(function() {
    'use strict';

    angular
        .module('app')
        .directive('pimEntityselector', pimEntityselector);


    function pimEntityselector(localStorageService, $http){
        return {
            restrict: 'E',
            scope: {
                key: '=', config: '=', value: '=', isValid: '=', isSubmit: '=', onChangeCallback: '&'
            },
            templateUrl: function(){
                return '/ui/default/types/entityselector/entityselector.html?v=' + APP_VERSION
            },
            link: function(scope, element, attrs){
                scope.writable = parseInt(attrs.writable) > 0;


                if((scope.value === undefined || scope.value == null) && scope.config.default != null){
                    scope.value = (scope.config.default);
                }

                if(parseInt(scope.value)){
                    scope.value = scope.value.toString();
                }

                scope.schema = localStorageService.get('schema');
                
                scope.$watch('value',function(data){

                    if(parseInt(scope.value)){
                        scope.value = scope.value.toString();
                    }

                    if(!scope.config.nullable){
                        var valExists = false;
                        for(var i = 0; i < scope.config.options.length; i++){
                            if(scope.config.options[i].id == scope.value){
                                valExists = true;
                                break;
                            }
                        }

                        if(!valExists){
                            scope.value = scope.config.options[0].id;
                        }
                    }
                    scope.newValue = scope.value;
                },true)

                scope.$watch('newValue',function(data){
                    if(!scope.writable){
                        return;
                    }

                    scope.onChangeCallback({key: scope.key, value: scope.newValue});
                },true)
            }
        }
    }

})();
