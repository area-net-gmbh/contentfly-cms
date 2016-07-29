(function() {
    'use strict';

    angular
        .module('app')
        .directive('pimEntity', pimEntity);


    function pimEntity(localStorageService){
        return {
            restrict: 'E',
            scope: {
                key: '=', config: '=', value: '=', isValid: '=', isSubmit: '=', onChangeCallback: '&'
            },
            templateUrl: function(){
                return 'types/entity/entity.html'
            },
            link: function(scope, element, attrs){
                var schema = localStorageService.get('schema')

                //Properties
                scope.entities = [];
                scope.value    = scope.value ? scope.value : '';

                //Functions
                scope.change = change;
                
                //Startup
                init();

                ////////////////////////////////
                
                function change(){
                    scope.onChangeCallback({key: scope.key, value: scope.value});
                }
                
                function init(){
                    scope.entities.push({
                        id: '',
                        name: '--- Bitte wählen ---'
                    });

                    for (var entity in schema) {
                        if(entity.substr(0, 4) == 'PIM\\') continue;
                        scope.entities.push({
                            id: entity,
                            name: schema[entity]["settings"]["label"]
                        });
                    }
                }


                /*scope.$watch('value',function(data){
                    scope.onChangeCallback({key: scope.key, value: scope.value});
                },true)*/
            }
        }
    }

})();
