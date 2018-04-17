(function() {
    'use strict';

    angular
        .module('app')
        .directive('pimCheckbox', pimCheckbox);


    function pimCheckbox($uibModal, $timeout, EntityService, localStorageService){
        return {
            restrict: 'E',
            scope: {
                key: '=', config: '=', value: '=', isValid: '=',  isSubmit: '=', onChangeCallback: '&'
            },
            templateUrl: function(){
                return '/ui/default/types/checkbox/checkbox.html?v=' + APP_VERSION
            },
            link: function(scope, element, attrs){
                var entity       = null;

                scope.$watch('checkboxObjects|filter:{selected:true}',function(data){
                    var values = [];
                    for(var i = 0; i < scope.checkboxObjects.length; i++) {
                        if(scope.checkboxObjects[i].selected) {
                            values.push(scope.checkboxObjects[i].id.toString());
                        }
                    }
                    scope.onChangeCallback({key: scope.key, value: values});
                },true);


                scope.$watch('value', function(data){
                   initCheckboxes();
                });

                //Properties
                scope.hide              = false;
                scope.readonly          = false;
                scope.schema            = null;
                scope.value             = scope.value ? scope.value : [];
                scope.checkboxClass     = null;
                scope.options           = [];
                scope.checkboxObjects   = [];

                //Functions
                scope.loadData      = loadData;

                //Startup
                init();

                /////////////////////////////////////


                function init(){

                    var permissions = localStorageService.get('permissions');
                    if(!permissions){
                        return;
                    }

                    scope.schema = localStorageService.get('schema')['PIM\\Option'];
                    if(scope.config.horizontalAlignment) {
                        scope.checkboxClass = 'checkbox-inline';
                    } else {
                        scope.checkboxClass = 'checkbox';
                    }

                    loadData();
                }

                function initCheckboxes(){
                  scope.checkboxObjects = [];

                  var compareArr = [];
                  for(var n = 0; n < scope.value.length; n++){
                    compareArr.push(scope.value[n].id.toString())
                  }
                  for(var i = 0; i < scope.options.length; i++) {
                    if(scope.value.length > 0) {
                      if($.inArray( scope.options[i].id.toString(), compareArr ) !== -1) {
                        scope.checkboxObjects.push({id: scope.options[i].id, value: scope.options[i].value, selected: true});
                      } else {
                        scope.checkboxObjects.push({id: scope.options[i].id, value: scope.options[i].value, selected: false});
                      }
                    } else {
                      scope.checkboxObjects.push({id: scope.options[i].id, value: scope.options[i].value, selected: false});
                    }
                  }
                }

                function loadData(){

                    var properties  = ['id', 'modified', 'created', 'user'];
                    var where       = {group: scope.config.group};

                    for (var key in scope.schema.list ) {
                        properties.push(scope.schema.list[key]);
                    }

                    var data = {
                        entity: 'PIM\\Option',
                        properties: properties,
                        where: where
                    };

                    EntityService.list(data).then(
                        function successCallback(response) {
                            scope.options = response.data.data;
                            initCheckboxes();
                        },
                        function errorCallback(response) {
                            scope.options = [];
                        }
                    );



                }



            }
        }
    }

})();
