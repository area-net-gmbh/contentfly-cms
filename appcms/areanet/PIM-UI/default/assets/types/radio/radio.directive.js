(function() {
    'use strict';

    angular
        .module('app')
        .directive('pimRadio', pimRadio);


    function pimRadio($uibModal, $timeout, EntityService, localStorageService){
        return {
            restrict: 'E',
            scope: {
                key: '=', config: '=', value: '=', isValid: '=',  isSubmit: '=', onChangeCallback: '&'
            },
            templateUrl: function(){
                return '/ui/default/types/radio/radio.html?v=' + APP_VERSION
            },
            link: function(scope, element, attrs){
                var entity       = null;

                scope.$watch('options.value',function(data){
                    scope.onChangeCallback({key: scope.key, value: data});
                },true);

                scope.$watch('value', function(data){
                    initRadio();
                });

                //Properties
                scope.hide              = false;
                scope.readonly          = false;
                scope.schema            = null;
                scope.value             = scope.value ? scope.value : [];
                scope.radioClass        = null;
                scope.options           = [];
                scope.radioObjects      = [];

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
                        scope.radioClass    = 'radio-inline';
                        scope.radioColumnns = scope.config.columns ? scope.config.columns : 4;
                    } else {
                        scope.radioClass = 'radio';
                    }

                    loadData();
                }

                function initRadio() {
                    scope.radioObjects = [];

                    if(scope.value) {
                        scope.options.value = scope.value.id;
                    }

                    for(var i = 0; i < scope.options.length; i++) {
                        scope.radioObjects.push({id: scope.options[i].id, value: scope.options[i].value});
                    }
                }

                function loadData(){
                    var properties  = ['id', 'modified', 'created', 'user'];
                    var where       = {group: scope.config.group};

                    for (var key in scope.schema.list ) {
                        properties.push(scope.schema.list[key]);
                    }

                    var sortSettings = {};
                    sortSettings['sorting'] = 'ASC';

                    var data = {
                        entity: 'PIM\\Option',
                        properties: properties,
                        where: where,
                        order: sortSettings
                    };

                    EntityService.list(data).then(
                        function successCallback(response) {
                            scope.options       = response.data.data;
                            initRadio();
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
