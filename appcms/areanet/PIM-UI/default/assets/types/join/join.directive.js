(function() {
    'use strict';

    angular
        .module('app')
        .directive('pimJoin', pimJoin);


    function pimJoin($uibModal, $timeout, EntityService, localStorageService, $rootScope){
        return {
            restrict: 'E',
            scope: {
                key: '=', config: '=', object: "=", value: '=', isValid: '=', isSubmit: '=', onChangeCallback: '&'
            },
            templateUrl: function(){
                return '/ui/default/types/join/join.html?v=' + APP_VERSION
            },
            link: function(scope, element, attrs){
                var itemsPerPage = 10;
                var entity       = null;

                //Properties
                scope.chooserOpened     = false;
                scope.currentPage       = 1;
                scope.deletable         = false;
                scope.hide              = false;
                scope.propertyCount     = 0;
                scope.objects           = [];
                scope.readonly          = false;
                scope.schema            = null;
                scope.selectedIndex     = 0;
                scope.totalPages        = 1;
                scope.writable          = false;
                scope.writable_object   = false;

                //Functions
                scope.addNewObject  = addNewObject;
                scope.change        = change;
                scope.chooseObject  = chooseObject;
                scope.closeChooser  = closeChooser;
                scope.editObject    = editObject;
                scope.keyPressed    = keyPressed;
                scope.loadData      = loadData;
                scope.openChooser   = openChooser;
                scope.removeObject  = removeObject;
                scope.setSelectedIndex = setSelectedIndex;

                //Startup
                init();

                /////////////////////////////////////

                function addNewObject(){
                    var modalInstance = $uibModal.open({
                        templateUrl: '/ui/default/views/form.html',
                        controller: 'FormCtrl as vm',
                        resolve: {
                            entity: function(){ return entity;},
                            title: function(){ return 'Neues Objekt anlegen'; },
                            object: function(){ return null; },
                            readonly: false,
                            lang: function(){ return scope.object.lang},
                            doCopy: false,
                            translateFrom:  function(){ null}
                        },
                        size: 'xl'
                    });

                    modalInstance.result.then(
                        function (newObject) {
                            if(newObject){
                                chooseObject(newObject);
                            }
                        },
                        function () {}
                    );
                }

                function change(){
                    scope.currentPage = 1;
                    loadData();
                }

                function chooseObject(object){

                    scope.value = object;
                    scope.onChangeCallback({key: scope.key, value: object.id});

                    scope.selectedIndex = 0;
                    scope.currentPage = 1;

                    scope.search        = '';
                    scope.objects       = [];


                    closeChooser();

                }

                function closeChooser(){
                    scope.chooserOpened = false;
                }

                function editObject(){

                    var modalInstance = $uibModal.open({
                        templateUrl: '/ui/default/views/form.html',
                        controller: 'FormCtrl as vm',
                        resolve: {
                            entity: function(){ return entity;},
                            object: function(){ return scope.value; },
                            readonly: false,
                            lang: function(){ return scope.object.lang},
                            doCopy: false,
                            translateFrom:  function(){ null}
                        },
                        size: 'xl'
                    });

                    modalInstance.result.then(
                        function (newObject) {
                            if(newObject){
                                scope.value = newObject;
                            }
                        },
                        function () {}
                    );
                }

                function isObject(val) {
                    if (val === null) { return false;}
                    return ( (typeof val === 'function') || (typeof val === 'object') );
                }
                
                function init(){

                    entity = $rootScope.getShortEntityName(scope.config.accept);

                    var permissions = localStorageService.get('permissions');
                    if(!permissions){
                        return;
                    }

                    if(scope.value){


                        if(!isObject(scope.value)) {

                          var data = {
                            entity: entity,
                            id: scope.value
                          }

                          EntityService.single(data).then(
                            function (response) {
                              scope.value = response.data.data;
                              scope.onChangeCallback({key: scope.key, value: scope.value.id});
                            },
                            function (data) {

                              if (data.status == 401) {
                                var modalInstance = $uibModal.open({
                                  templateUrl: '/ui/default/views/partials/relogin.html?v=' + APP_VERSION,
                                  controller: 'ReloginCtrl as vm',
                                  backdrop: 'static'
                                });

                                modalInstance.result.then(
                                  function () {
                                    init();
                                  },
                                  function () {
                                    $uibModalInstance.close();
                                    $location.path('/logout');
                                  }
                                );

                              } else {

                                var modalInstance = $uibModal.open({
                                  templateUrl: '/ui/default/views/partials/modal.html?v=' + APP_VERSION,
                                  controller: 'ModalCtrl as vm',
                                  resolve: {
                                    title: function () {
                                      return data.statusText;
                                    },
                                    body: function () {
                                      return data.data.message;
                                    },
                                    hideCancelButton: function () {
                                      return false;
                                    }
                                  }
                                });

                                modalInstance.result.then(
                                  function (doDelete) {
                                    $uibModalInstance.close();
                                  },
                                  function () {
                                    $uibModalInstance.close();
                                  }
                                );
                              }
                            }
                          );
                        }else{
                          scope.onChangeCallback({key: scope.key, value: scope.value.id});
                        }
                    }

                    scope.hide              = !permissions[entity].readable;
                    scope.readonly          = parseInt(attrs.readonly) > 0;
                    scope.writable_object   = permissions[entity].writable && !scope.readonly;
                    scope.writable          = parseInt(attrs.writable) > 0;

                    scope.schema    = localStorageService.get('schema')[entity];

                    scope.propertyCount = Object.keys(scope.schema.list).length;
                }

                function keyPressed(event){
                    switch(event.keyCode) {
                        case 40:
                            if (scope.selectedIndex < scope.objects.length - 1) scope.selectedIndex++;
                            break;
                        case 38:
                            if (scope.selectedIndex > 0) scope.selectedIndex--;
                            break;
                        case 13:
                            chooseObject(scope.objects[scope.selectedIndex]);
                            event.stopPropagation();
                            break;
                        case 39:
                            if (scope.currentPage < scope.totalPages){
                                scope.currentPage++;
                                loadData();
                            }
                            break;
                        case 37:
                            if(scope.currentPage > 1){
                                scope.currentPage--;
                                loadData();
                            }
                            break;
                        case 27:
                            closeChooser();
                            event.stopPropagation();
                            break;
                    }
                }

                function loadData(){
                    var where = scope.search ? {fulltext: scope.search} : {};

                    var properties = ['id', 'modified', 'created', 'user'];
                    if(scope.schema.settings.isSortable){
                        properties.push('sorting');
                    }
                    for (var key in scope.schema.list ) {
                        properties.push(scope.schema.list[key]);
                    }

                    var data = {
                        entity: entity,
                        currentPage: scope.currentPage,
                        itemsPerPage: itemsPerPage,
                        where: where,
                        properties: properties,
                        lang: scope.object.lang
                    };
                    EntityService.list(data).then(
                        function successCallback(response) {
                            scope.totalPages    = Math.ceil(response.data.totalItems / itemsPerPage);
                            scope.objects       = response.data.data;
                            scope.selectedIndex = 0;
                        },
                        function errorCallback(response) {
                            scope.objects = [];
                        }
                    );
                }

                function openChooser(){
                    scope.chooserOpened = true;

                    $timeout(function () {
                        element.find('#search').focus();
                    }, 50);

                    loadData();
                }

                function removeObject(){
                    scope.value = null;
                    scope.onChangeCallback({key: scope.key, value: ''});
                }

                function setSelectedIndex(index){
                    scope.selectedIndex = index;
                }

            }
        }
    }

})();
