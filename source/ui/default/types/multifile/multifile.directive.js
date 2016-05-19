(function() {
    'use strict';

    angular
        .module('app')
        .directive('pimMultifile', pimMultifile);


    function pimMultifile($uibModal, Upload, $timeout, localStorageService) {
        return {
            restrict: 'E',
            scope: {
                key: '=', config: '=', value: '=', isValid: '=', isSubmit: '=', onChangeCallback: '&'
            },
            templateUrl: function () {
                return 'types/multifile/multifile.html'
            },
            link: function (scope, element, attrs) {

                //Properties

                //Functions
                scope.addFile       = addFile;
                scope.editFile      = editFile;
                scope.removeFile    = removeFile;
                scope.uploadFile    = uploadFile;

                ///////////////////////////

                function addFile() {
                    var modalInstance = $uibModal.open({
                        templateUrl: 'views/files.html',
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
                        }
                    });

                    modalInstance.result.then(
                        function (fileData) {

                            if (fileData) {
                                scope.value = scope.value ? scope.value : [];
                                scope.value.push(fileData);

                                triggerUpdate();
                            }

                        },
                        function () {}
                    );
                }

                function editFile(index, id, title) {

                    var modalInstance = $uibModal.open({
                        templateUrl: 'views/partials/file-edit.html',
                        controller: 'FileEditCtrl as vm',
                        resolve: {
                            modaltitle: function () {
                                return 'Titel Datei ' + id + ' bearbeiten';
                            },
                            title: function () {
                                return title;
                            },
                            id: function () {
                                return id;
                            }
                        }
                    });

                    modalInstance.result.then(
                        function (title) {
                            scope.value[index]["title"] = title;
                        },
                        function () {}
                    );

                }

                function removeFile(index) {
                    scope.value.splice(index, 1);

                    triggerUpdate();
                }

                function triggerUpdate(){
                    var values = [];
                    for (var index in scope.value) {
                        values.push(scope.value[index].id);
                    }
                    scope.onChangeCallback({key: scope.key, value: values});
                }

                function uploadFile(files, errFiles) {
                    scope.fileUploads = files;

                    scope.value = scope.value ? scope.value : [];

                    angular.forEach(files, function (file) {
                        file.upload = Upload.upload({
                            url: '/file/upload',
                            headers: {'X-Token': localStorageService.get('token')},
                            data: {file: file}
                        });

                        file.upload.then(
                            function (response) {
                                file.result = response.data;

                                scope.value.push(response.data.data);
                                triggerUpdate();

                                $timeout(function () {
                                    scope.fileUploads = null;
                                }, 1000);

                            },
                            function (response) {
                                if (response.status > 0)  scope.errorMsg = response.status + ': ' + response.data;
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