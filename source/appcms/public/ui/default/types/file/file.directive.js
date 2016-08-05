(function() {
    'use strict';

    angular
        .module('app')
        .directive('pimFile', pimFile);


    function pimFile($uibModal, Upload, $timeout, localStorageService){
        return {
            restrict: 'E',
            scope: {
                key: '=', config: '=', value: '=', isValid: '=', isSubmit: '=', onChangeCallback: '&'
            },
            templateUrl: function(){
                return '/ui/default/types/file/file.html'
            },
            link: function(scope, element, attrs){

                //Properties
                scope.errorMsg      = null;
                scope.fileUpload    = {};
                scope.readable      = true;
                scope.uploadable    = true;

                //Functions
                scope.addFile       = addFile;
                scope.editFile      = editFile;
                scope.removeFile    = removeFile;
                scope.uploadFile    = uploadFile;


                //Startup
                init();

                /////////////////////////

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

                    modalInstance.result.then(function (fileData) {
                        var accept = scope.config.accept.replace('*', '');
                        var fileDataType = fileData.type.substr(0, accept.length);

                        if (accept != fileDataType) {
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
                            scope.value = fileData;
                            scope.onChangeCallback({key: scope.key, value: fileData['id']});
                        }
                    }, function () {
                    });
                }

                function addVideo(){
                    var modalInstance = $uibModal.open({
                        templateUrl: '/ui/default/views/partials/video-add.html',
                        controller: 'VideoAddCtrl as vm',
                        resolve: {
                            modaltitle: function () {
                                return 'Neues Youtube-Video hinzufügen';
                            },
                            property: function () {
                                return scope.key;
                            }
                        }
                    });

                    modalInstance.result.then(
                        function (fileData) {
                            if (fileData) {
                                scope.value = fileData;
                                scope.onChangeCallback({key: scope.key, value: fileData['id']});
                            }
                        },
                        function () {}
                    );
                }

                function editFile(){
                    
                    var modalInstance = $uibModal.open({
                        templateUrl: '/ui/default/views/form.html',
                        controller: 'FormCtrl as vm',
                        resolve: {
                            entity: function(){ return 'PIM\\File';},
                            title: function(){ return 'Objekt ' + id + ' bearbeiten'; },
                            object: function(){ return scope.value; }
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

                function init(){
                    var permissions = localStorageService.get('permissions')

                    scope.readable   = permissions['PIM\\File'].readable;
                    scope.uploadable = permissions['PIM\\File'].writable;
                    scope.writable   = parseInt(attrs.writable) > 0;
                    
                }

                function removeFile () {
                    scope.value = null;
                    scope.onChangeCallback({key: scope.key, value: null});
                }

                function uploadFile(file, errFiles){
                    scope.fileUpload = file;

                    if (file) {
                        file.upload = Upload.upload({
                            url: '/file/upload',
                            headers: {'X-Token': localStorageService.get('token')},
                            data: {file: file}
                        });

                        file.upload.then(
                            function (response) {
                                scope.value = response.data.data;
                                scope.onChangeCallback({key: scope.key, value: response.data.data.id});

                                $timeout(function () {
                                    scope.fileUpload = null;
                                }, 1000);
                            },
                            function (response) {
                                if (response.status > 0) scope.errorMsg = response.status + ': ' + response.data;
                            },
                            function (evt) {
                                file.progress = Math.min(100, parseInt(100.0 * evt.loaded / evt.total));
                            }
                        );
                    }
                }
            }
        }
    }

})();
