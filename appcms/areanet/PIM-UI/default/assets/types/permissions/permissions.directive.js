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
                return '/ui/default/types/permissions/permissions.html?v=' + APP_VERSION
            },
            link: function(scope, element, attrs){
                //Properties
                scope.schema                = localStorageService.get('schema');
                scope.matrix                = [];
                scope.matrixCustom          = [];
                scope.permission_extended   = null;

                //Functions
                scope.changeExtendedPermission  = changeExtendedPermission;
                scope.changePermission          = changePermission;
                scope.showExtendedPermissions   = showExtendedPermissions;

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
                                scope.matrix[i].export    = scope.value[key].export;
                                scope.matrix[i].extended  = scope.value[key].extended ? JSON.parse(scope.value[key].extended) : null;
                                break;
                            }
                        }

                        for(var i = 0; i < scope.matrixCustom.length; i++){
                            if(scope.value[key].entityName == scope.matrixCustom[i].name){
                                scope.matrixCustom[i].readable  = scope.value[key].readable;
                                scope.matrixCustom[i].writable  = scope.value[key].writable;
                                scope.matrixCustom[i].deletable = scope.value[key].deletable;
                                scope.matrixCustom[i].export    = scope.value[key].export;
                                scope.matrixCustom[i].extended  = scope.value[key].extended ? JSON.parse(scope.value[key].extended) : null;
                                break;
                            }
                        }
                    }
                },true);

                //////////////////////////////

                function changeExtendedPermission(index, mode){
                    if(mode == 'tabPermission' && !scope.permission_extended.data[index].tabTitle){
                        return;
                    }

                    switch(scope.permission_extended.data[index][mode]){
                        case 0:
                            scope.permission_extended.data[index][mode] = -1;
                            break;
                        case 1:
                            scope.permission_extended.data[index][mode] = 0;
                            break;
                        case -1:
                            scope.permission_extended.data[index][mode] = 1;
                            break;
                    }

                    var entityIndex = scope.permission_extended.data[index].entityIndex;
                    var matrix      = scope.permission_extended.data[index].isCustomEntity ? 'matrixCustom' : 'matrix';

                    if(!scope[matrix][entityIndex]['extended']){
                        scope[matrix][entityIndex]['extended'] = {'tabPermission': {}, 'fieldPermission': {}};
                    }else{
                        scope[matrix][entityIndex]['extended'][mode] = {};
                    }

                    for(var i = 0; i < scope.permission_extended.data.length; i++){
                        if(mode == 'tabPermission' && !scope.permission_extended.data[i].tabTitle){
                            continue;
                        }
                        if(scope.permission_extended.data[i][mode] != -1){

                            var field  = null;
                            var value  = scope.permission_extended.data[i][mode];
                            if(mode == 'tabPermission'){
                                field = scope.permission_extended.data[i].tabName;
                            }else{
                                field = scope.permission_extended.data[i].fieldName;
                            }

                            scope[matrix][entityIndex]['extended'][mode][field] = value;

                        }
                    }


                    if(!Object.keys(scope[matrix][entityIndex]['extended']['tabPermission']).length && !Object.keys(scope[matrix][entityIndex]['extended']['fieldPermission']).length){
                        scope[matrix][entityIndex]['extended'] = null;
                    }


                    scope.onChangeCallback({key: scope.key, value: scope.matrix.concat(scope.matrixCustom)});

                }

                function changePermission(index, mode, isCustomMatrix){
                    var matrix = isCustomMatrix ? 'matrixCustom' : 'matrix';

                    if(mode == 'export'){
                        scope[matrix][index][mode] = scope[matrix][index][mode] == 0 ? 2 : 0;
                    }else{
                      switch(scope[matrix][index][mode]){
                        case 0:
                          scope[matrix][index][mode] = 1;
                          break;
                        case 1:
                          scope[matrix][index][mode] = 3;
                          break;
                        case 3:
                          scope[matrix][index][mode] = 2;
                          break;
                        case 2:
                          scope[matrix][index][mode] = 0;
                          break;
                      }
                    }

                    scope.onChangeCallback({key: scope.key, value: scope.matrix.concat(scope.matrixCustom)});
                }
                
                function init(){
                    var excludedEntities = ['PIM\\Permission', 'PIM\\Log', 'PIM\\Tag', 'PIM\\PushToken', 'PIM\\ThumbnailSetting','_hash'];

                    var index       = 0;
                    var indexCustom = 0;
                    for (var entityName in scope.schema) {
                        if(excludedEntities.indexOf(entityName) > -1){
                            continue;
                        }

                        var data = {
                            'name' : entityName,
                            'label' : scope.schema[entityName].settings.label,
                            'readable' : 0,
                            'writable' : 0,
                            'deletable' : 0,
                            'export' : 0
                        };

                        if(entityName.substr(0,4) == 'PIM\\'){
                            data.index = index;
                            scope.matrix.push(data);
                            index++;
                        }else{
                            data.index = indexCustom;
                            scope.matrixCustom.push(data);
                            indexCustom++;
                        }

                    }
                }

                function showExtendedPermissions(config, isCustomEntity){
                    if(!config){
                        scope.permission_extended = null;
                    }else{
                        scope.permission_extended = config;
                        var data = [];

                        var matrix = isCustomEntity ? 'matrixCustom' : 'matrix';


                        for(var fieldName in scope.schema[config.name].properties){
                            if(scope.schema[config.name].properties[fieldName].hide){
                                continue;
                            }

                            var tabName   = scope.schema[config.name].properties[fieldName].tab;

                            data.push({
                                'tabName'           : tabName,
                                'tabTitle'          : scope.schema[config.name].settings.tabs[scope.schema[config.name].properties[fieldName].tab].title,
                                'tabPermission'     : scope[matrix][config.index].extended && scope[matrix][config.index].extended['tabPermission'][tabName] >= 0 ? scope[matrix][config.index].extended['tabPermission'][tabName] : -1,
                                'fieldName'         : fieldName,
                                'fieldTitle'        : scope.schema[config.name].properties[fieldName].label,
                                'fieldPermission'   : scope[matrix][config.index].extended && scope[matrix][config.index].extended['fieldPermission'][fieldName] >= 0 ? scope[matrix][config.index].extended['fieldPermission'][fieldName] : -1,
                                'entityIndex'       : config.index,
                                'isCustomEntity'    : isCustomEntity
                            });
                        }

                        data.sort(function(a,b){
                            if (a.tabTitle < b.tabTitle)
                                return -1;
                            if (a.tabTitle > b.tabTitle)
                                return 1;
                            return 0;
                        });

                        var lastTabName = '';
                        for(var i = 0; i < data.length; i++){
                            if(lastTabName == data[i].tabName){
                                data[i].tabTitle = '';
                            }
                            lastTabName = data[i].tabName;
                        }

                        scope.permission_extended.data = data;
                    }


                }
            }
        }
    }

})();
