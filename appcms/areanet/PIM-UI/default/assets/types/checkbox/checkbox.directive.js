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

                scope.$watch('value',function(data){

                    console.log(scope.value);


                },true);

                //Properties
                scope.hide          = false;
                scope.readonly      = false;
                scope.schema        = null;
                scope.value = scope.value ? scope.value : [];
                scope.checkboxClass = null;
                scope.options = [];

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
                            scope.options       = response.data.data;
                            console.log(scope.options);
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
