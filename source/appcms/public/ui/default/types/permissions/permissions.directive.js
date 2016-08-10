(function() {
    'use strict';

    angular
        .module('app')
        .directive('pimPermissions', pimPermissions);


    function pimPermissions(localStorageService){
        return {
            restrict: 'E',
            scope: {
                key: '=', config: '=', value: '=', isValid: '=', isSubmit: '=', onChangeCallback: '&'
            },
            templateUrl: function(){
                return '/ui/default/types/permissions/permissions.html'
            },
            link: function(scope, element, attrs){
                //Properties
                scope.schema = localStorageService.get('schema');
                scope.matrix = [];

                //Functions
                scope.changePermission = changePermission;
                
                //Startup
                init();

                scope.$watch('value',function(data){
                    if(scope.matrix.length == 0){
                        return;
                    }

                    for (var key in scope.value) {
                        for(var i = 0; i < scope.matrix.length; i++){
                            if(scope.value[key].entityName == scope.matrix[i].name){
                                scope.matrix[i].readable  = scope.value[key].readable;
                                scope.matrix[i].writable  = scope.value[key].writable;
                                scope.matrix[i].deletable = scope.value[key].deletable;
                                break;
                            }
                        }
                    }
                },true);

                //////////////////////////////
                
                function changePermission(index, mode){
                    switch(scope.matrix[index][mode]){
                        case 0:
                            scope.matrix[index][mode] = 1;
                            break;
                        case 1:
                            scope.matrix[index][mode] = 2;
                            break;
                        case 2:
                            scope.matrix[index][mode] = 0;
                            break;
                    }

                    scope.onChangeCallback({key: scope.key, value: scope.matrix});
                }
                
                function init(){
                    var excludedEntities = ['PIM\\Log', 'PIM\\Tag', 'PIM\\PushToken', 'PIM\\Group', 'PIM\\ThumbnailSetting'];

                    var index = 0;
                    for (var entityName in scope.schema) {
                        if(excludedEntities.indexOf(entityName) > -1){
                            continue;
                        }

                        scope.matrix.push({
                            'index': index,
                            'name' : entityName,
                            'label' : scope.schema[entityName].settings.label,
                            'readable' : 0,
                            'writable' : 0,
                            'deletable' : 0
                        });

                        index++;
                    }
                }
            }
        }
    }

})();
