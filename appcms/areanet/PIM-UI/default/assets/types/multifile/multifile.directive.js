(function() {
    'use strict';

    angular
        .module('app')
        .directive('pimMultifile', pimMultifile);


    function pimMultifile($uibModal, Upload, $timeout, EntityService, localStorageService, $rootScope) {
        return {
            restrict: 'E',
            scope: {
                key: '=', config: '=', value: '=',  isValid: '=', isSubmit: '=', onChangeCallback: '&'
            },
            templateUrl: function () {
                return '/ui/default/types/multifile/multifile.html?v=' + APP_VERSION
            },
            link: function (scope, element, attrs) {

                //Properties
                scope.readable      = true;
                scope.uploadable    = true;
                scope.readonly      = false;

                //Functions
                scope.addFile       = addFile;
                scope.editFile      = editFile;
                scope.openFile      = openFile;
                scope.disableObject = disableObject;
                scope.removeFile    = removeFile;
                scope.uploadFile    = uploadFile;

                scope.sortableOptions = {
                    stop: function(e,ui){
                        //console.log("STOPM SORT");
                        triggerUpdate();
                    },
                    disabled:!scope.config.sortable
                };

                //Startup
                init();

                ///////////////////////////

                function addFile() {
                    var modalInstance = $uibModal.open({
                        templateUrl: '/ui/default/views/files.html',
                        controller: 'FilesCtrl as vm',
                        resolve: {
                            modaltitle: function () {
                                return 'Datei hinzufügen';
                            },
                            property: function () {
                                return scope.key;
                            },
                            pimEntity: function () {
                                return true;
                            }
                        },
                        size: 'xl'
                    });

                    modalInstance.result.then(
                        function (fileData) {
                            var accept = scope.config.accept.replace('*', '');
                            accept = accept.split(',');
                            var fileDataType = fileData.type;
                            var matches = false;

                            for(var i = 0; i < accept.length; i++) {
                                if(fileDataType.includes(accept[i])) {
                                    matches = true;
                                    break;
                                }
                            }

                            if(!matches) {
                                var modalInstance = $uibModal.open({
                                    templateUrl: '/ui/default/views/partials/modal.html',
                                    controller: 'ModalCtrl as vm',
                                    resolve: {
                                        title: function () {
                                            return 'Fehler bei der Dateiauswahl';
                                        },
                                        body: function () {
                                            return 'Dieser Dateityp kann an dieser Stelle nicht ausgewählt werden.';
                                        },
                                        hideCancelButton: true
                                    }
                                });
                                return;
                            }

                            if (fileData) {

                                scope.value = scope.value ? scope.value : [];


                                var newData = {};

                                if(scope.config.mappedBy){
                                    newData[scope.config.mappedBy] =  fileData;
                                    if(scope.config.sortable){
                                        newData['isActive'] = true;
                                    }
                                }else{
                                    newData = fileData;
                                }

                                scope.value.push(newData);


                                triggerUpdate();
                            }

                        },
                        function () {}
                    );
                }

                function editFile(index, id, title) {

                    var modalInstance = $uibModal.open({
                        templateUrl: '/ui/default/views/form.html',
                        controller: 'FormCtrl as vm',
                        resolve: {
                            entity: function(){ return 'PIM\\File';},
                            title: function(){ return 'Objekt ' + id + ' bearbeiten'; },
                            object: function(){ return scope.config.mappedBy ? scope.value[index][scope.config.mappedBy] : scope.value[index]; },
                            readonly: false,
                            lang: function(){ return scope.object ? scope.object.lang : null},
                            doCopy: false,
                            translateFrom:  function(){ null}
                        },
                        size: 'xl'
                    });

                    modalInstance.result.then(
                        function (newObject) {
                            if(scope.config.mappedBy){
                                scope.value[index][scope.config.mappedBy] = newObject;
                            }else{
                                scope.value[index] = newObject;
                            }

                        },
                        function () {}
                    );

                }

                function disableObject(index){
                    var id     = scope.value[index].id;
                    var object = scope.value[index];

                    if(!scope.config.mappedBy || typeof object.isActive == "undefined"){
                        return;
                    }

                    object.isActive = !object.isActive;

                    var entityAcceptFrom = $rootScope.getShortEntityName(scope.config.acceptFrom);

                    var data = {
                        entity: entityAcceptFrom,
                        id: id,
                        data: {
                            isActive:object.isActive
                        }
                    };

                    EntityService.update(data).then(
                        function successCallback(response) {

                        },
                        function errorCallback(response) {

                        }
                    );

                }

                function openFile(index, id, name) {
                    window.open('/file/get/'+id+'/'+name, '_blank');
                }

                function init(){

                    var permissions = localStorageService.get('permissions')
                    if(!permissions){
                        return;
                    }
                    
                    scope.readable      = permissions['PIM\\File'].readable;
                    scope.uploadable    = permissions['PIM\\File'].writable;
                    scope.readonly        = parseInt(attrs.readonly) > 0;
                    if(scope.config.acceptFrom){
                        var entityForm = $rootScope.getShortEntityName(scope.config.acceptFrom);

                        scope.deletable         = permissions[entityForm].deletable;
                        scope.writable_object   = permissions['PIM\\File'].writable && !scope.readonly;
                        scope.writable          = parseInt(attrs.writable) > 0 && permissions[entityForm].writable > 0;
                    }else{

                        scope.writable_object   = permissions['PIM\\File'].writable && !scope.readonly;
                        scope.deletable         = parseInt(attrs.writable) > 0;
                        scope.writable          = parseInt(attrs.writable) > 0;
                    }
                    
                }

                function removeFile(index) {
                    scope.value.splice(index, 1);

                    triggerUpdate();
                }

                function triggerUpdate(){
                    var values = [];
                    for (var index in scope.value) {
                        if(scope.config.mappedBy){
                            if(scope.value[index][scope.config.mappedBy]) values.push(scope.value[index][scope.config.mappedBy].id);
                        }else{
                            values.push(scope.value[index].id);
                        }

                    }
                    scope.onChangeCallback({key: scope.key, value: values});
                }

                function uploadFile(files, errFiles) {
                    scope.fileUploads = files;

                    scope.value = scope.value ? scope.value : [];

                    angular.forEach(files, function (file) {
                        file.upload = Upload.upload({
                            url: '/file/upload',
                            data: {file: file}
                        });

                        file.upload.then(
                            function (response) {
                                file.result = response.data;

                                if(scope.config.mappedBy) {
                                    var subObject = {};
                                    subObject[scope.config.mappedBy] = response.data.data;
                                    scope.value.push(subObject);
                                }else{
                                    scope.value.push(response.data.data);
                                }
                                triggerUpdate();

                                $timeout(function () {
                                    scope.fileUploads = null;
                                }, 1000);

                            },
                            function (response) {
                              scope.fileUploads = null;
                              if (response.status > 0) scope.errorMsg = 'Fehler: ' + response.status + ': ' + response.data.message;
                            },
                            function (evt) {
                                file.progress = Math.min(100, parseInt(100.0 * evt.loaded / evt.total));
                            }
                        );
                    });

                }
            }
        }
    }

})();