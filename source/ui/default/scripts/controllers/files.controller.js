(function() {
    'use strict';

    angular
        .module('app')
        .controller('FilesCtrl', FilesCtrl);

    function FilesCtrl($scope, $cookies, localStorageService, $uibModalInstance, $routeParams, $timeout, $http, $uibModal, pimEntity, Upload, angularGridInstance, modaltitle, property, EntityService){
        var vm = this;
        var oldPageNumber = 1;

        //Properties
        vm.doUpload = false;
        vm.objects = [];
        vm.objectsAvailable = false;
        vm.objectsNotAvailable = false;

        vm.modaltitle = modaltitle;

        vm.itemsPerPage = 0;
        vm.totalItems = 0;
        vm.currentPage = 1;

        vm.angularGridOptions = {
            refreshOnImgLoad : true
        };

        vm.entity = 'PIM\\File';

        vm.fileUploads = null;

        var schema = localStorageService.get('schema');
        vm.schema = schema[vm.entity];

        vm.sortProperty = vm.schema.settings.sortBy;
        vm.sortOrder    = vm.schema.settings.sortOrder;

        vm.filter = {};
        vm.filterIsOpen = false;
        vm.filterBadge = 0;
        vm.filterJoins = {};

        //Functions
        vm.closeFilter = closeFilter;
        vm.delete = doDelete;
        vm.loadData = loadData;
        vm.openForm = openForm;
        vm.executeFilter = executeFilter;
        vm.paginationChanged = paginationChanged;
        vm.resetFilter = resetFilter;
        vm.selectFile = selectFile;
        vm.sortBy = sortBy;
        vm.uploadFile = uploadFile;
        vm.uploadMultiFile = uploadMultiFile;

        //Startup
        loadFilters();

        /////////////////////////

        function closeFilter (){
            vm.filterIsOpen = false;
        }

        function doDelete(id, name){
            var modalInstance = $uibModal.open({
                templateUrl: 'views/partials/modal.html',
                controller: 'ModalCtrl as vm',
                resolve: {
                    title: function(){ return 'Datei löschen'; },
                    body: function(){ return 'Wollen Sie die Datei  "' + name + '" wirklich löschen?'; },
                    hideCancelButton: false
                }
            });

            modalInstance.result.then(
                function (doDelete) {
                    if(doDelete){

                        angular.element('#fileitem_' + id).hide();
                        vm.objectsAvailable = false;

                        var data = {
                            entity: vm.entity,
                            id: id
                        };

                        EntityService.delete(data).then(
                            function successCallback(response) {
                                loadData();
                            },
                            function errorCallback(response) {
                                var modalInstance = $uibModal.open({
                                    templateUrl: 'views/partials/modal.html',
                                    controller: 'ModalCtrl as vm',
                                    resolve: {
                                        title: function(){ return 'Fehler beim Löschen'; },
                                        body: function(){ return response.data.message; },
                                        hideCancelButton: true
                                    }
                                });
                            }
                        );

                    }
                },
                function () {}
            );
        }

        function openForm(object){
            var modalInstance = $uibModal.open({
                templateUrl: 'views/form.html',
                controller: 'FormCtrl as  vm',
                resolve: {
                    entity: function(){ return vm.entity;},
                    title: function(){ return 'Objekt bearbeiten'; },
                    object: function(){ return object; }
                },
                size: 'xl',
                backdrop: 'static'
            });

            modalInstance.result.then(
                function (isSaved) {
                    if(isSaved){
                        loadData();
                    }
                },
                function () {}
            );
        }

        function executeFilter() {

            var badgeCount = 0;
            for (var key in vm.filter) {
                if(vm.filter[key]){
                    badgeCount++;
                }
            }

            vm.filterBadge = badgeCount;

            loadData();
        }

        function generateTree(entity, field, data, depth){
            var joinSchema = localStorageService.get('schema')[entity];

            vm.filterJoins[field] = vm.filterJoins[field] ? vm.filterJoins[field] : [];

            for(var i = 0; i < data.length; i++){
                var filler = '--'.repeat(depth);
                filler = filler ? filler + ' ' : filler
                data[i]['pim_filterTitle'] = filler + data[i][joinSchema.list[Object.keys(joinSchema.list)[0]]];
                vm.filterJoins[field].push(data[i]);
                if(data[i]['treeChilds']){
                    var subDepth = depth + 1;
                    generateTree(entity, field, data[i]['treeChilds'], subDepth);
                }
            }
        }

        function loadData() {
            var sortSettings = {};
            sortSettings[vm.sortProperty] = vm.sortOrder;

            var filter = {};
            for (var key in vm.filter) {
                if(vm.filter[key]){
                    filter[key] = vm.filter[key];
                }
            }
            vm.objectsAvailable = false;

            var properties = ['id', 'modified', 'created', 'user'];

            for (key in vm.schema.list ) {
                properties.push(vm.schema.list[key]);
            }
            
            var data = {
                entity: 'PIM\\File',
                currentPage: vm.currentPage,
                order: sortSettings,
                where: filter,
                properties: properties
            };

            EntityService.list(data).then(
                function successCallback(response) {

                    if(vm.itemsPerPage === 0) {
                        vm.itemsPerPage = response.data.itemsPerPage;
                    }

                    vm.totalItems = response.data.totalItems;
                    vm.objects = (response.data.data);
                    vm.objectsAvailable = true;
                    vm.objectsNotAvailable = false;

                    angularGridInstance.gallery.refresh();
                },
                function errorCallback(response) {

                    vm.objectsAvailable = false;
                    vm.objectsNotAvailable = true;
                    vm.objects = {};

                    angularGridInstance.gallery.refresh();
                }
            );

        }

        function loadFilters(){
            for (var key in vm.schema.properties) {

                if(vm.schema.properties[key].type == 'join' && vm.schema.properties[key].isFilterable){
                    var entity = null;
                    if(vm.schema.properties[key].accept.substr(0,7) == 'Areanet'){
                        entity = vm.schema.properties[key].accept.replace('Areanet\\PIM\\Entity\\', 'PIM\\');
                    }else{
                        entity =  vm.schema.properties[key].accept.replace('Custom\\Entity\\', '').replace('\\', '');
                    }

                    if(localStorageService.get('schema')[entity].settings.type == 'tree') {

                        EntityService.tree({entity: entity}).then(
                            (function(entity, key) {
                                return function(response) {
                                    generateTree(entity, key, response.data.data, 0)
                                }
                            })(entity, key),
                            function errorCallback(response) {
                            }
                        );
                    }else{
                        var joinSchema = localStorageService.get('schema')[entity];

                        var properties = ['id', 'modified', 'created', 'user'];
                        if(joinSchema.settings.isSortable){
                            properties.push('sorting');
                        }
                        for (var key in joinSchema.list ) {
                            properties.push(joinSchema.list[key]);
                        }

                        EntityService.list({entity: entity, properties: properties}).then(
                            (function(entity, key) {
                                return function(response) {
                                    vm.filterJoins[key] = response.data.data;

                                    for (var i = 0; i < vm.filterJoins[key].length; i++) {
                                        if (!vm.filterJoins[key][i]['pim_filterTitle']) {
                                            vm.filterJoins[key][i]['pim_filterTitle'] = vm.filterJoins[key][i][joinSchema.list[Object.keys(joinSchema.list)[0]]];
                                        }
                                    }
                                }
                            })(entity, key),
                            function errorCallback(response) {
                            }
                        );
                    }

                }else if(vm.schema.properties[key].type == 'multijoin' && vm.schema.properties[key].isFilterable){
                    var entity = null;
                    if(vm.schema.properties[key].accept.substr(0,7) == 'Areanet'){
                        entity = vm.schema.properties[key].accept.replace('Areanet\\PIM\\Entity\\', 'PIM\\');
                    }else{
                        entity =  vm.schema.properties[key].accept.replace('Custom\\Entity\\', '').replace('\\', '');
                    }

                    var field = key;

                    if(localStorageService.get('schema')[entity].settings.type == 'tree'){

                        EntityService.tree({entity: entity}).then(
                            (function(entity, key) {
                                return function(response) {
                                    generateTree(entity, key, response.data.data, 0)
                                }
                            })(entity, key),
                            function errorCallback(response) {
                            }
                        );
                    }else{

                        EntityService.list({entity: entity}).then(
                            (function(entity, key) {
                                return function(response) {
                                    var joinSchema = localStorageService.get('schema')[entity];
                                    vm.filterJoins[key] = response.data.data;

                                    for(var i = 0; i < vm.filterJoins[key].length; i++){
                                        if(!vm.filterJoins[key][i]['pim_filterTitle']){
                                            vm.filterJoins[key][i]['pim_filterTitle'] = vm.filterJoins[key][i][joinSchema.list[Object.keys(joinSchema.list)[0]]];
                                        }
                                    }
                                }
                            })(entity, key),
                            function errorCallback(response) {
                            }
                        );
                    }


                }else if(vm.schema.properties[key].isFilterable){
                    var field = key;

                    var data = {
                        entity: vm.entity,
                        properties: [field],
                        groupBy: field
                    }

                    data['order'] = {};
                    data['order'][field] = "ASC";

                    EntityService.list(data).then(
                        (function(entity, key) {
                            return function(response) {
                                vm.filterJoins[key] = response.data.data;

                                for(var i = 0; i < vm.filterJoins[key].length; i++){
                                    if(!vm.filterJoins[key][i]['pim_filterTitle']){
                                        vm.filterJoins[key][i]['pim_filterTitle'] = vm.filterJoins[key][i][key];
                                    }
                                }
                            }
                        })(entity, key),
                        function errorCallback(response) {
                        }
                    );
                }


            }
        }

        function paginationChanged(newPageNumber) {
            if(oldPageNumber == newPageNumber){
                return;
            }

            vm.objectsAvailable = false;
            vm.currentPage = newPageNumber;
            oldPageNumber = newPageNumber;

            loadData();
        };

        function resetFilter() {
            vm.filter = {};
            vm.filterBadge = 0;
            vm.filterIsOpen = false;

            loadData();
        }

        function selectFile(object){
            if(!modaltitle) return;

            $uibModalInstance.close(object);
        }

        function sortBy(property) {
            if(vm.sortProperty === property) {
                if(vm.sortOrder === 'DESC') {
                    vm.sortOrder = 'ASC';
                } else {
                    vm.sortOrder = 'DESC';
                }
            } else {
                vm.sortProperty = property;
                vm.sortOrder = 'ASC';
            }

            loadData();
        }

        function uploadFile(file, id, errFiles) {
            vm.fileUploads = [file];

            file.upload = Upload.upload({
                url: '/file/upload',
                headers: {'X-Token': localStorageService.get('token')},
                data: {file: file, id: id, folder: vm.filter['folder']}
            });
            file.upload.then(
                function (response) {

                    file.result = response.data;

                    $timeout(function () {
                        vm.fileUploads = null;
                        loadData();
                    }, 1000);

                },
                function (response) {
                    if (response.status > 0) vm.errorMsg = response.status + ': ' + response.data;
                },
                function (evt) {
                    file.progress = Math.min(100, parseInt(100.0 * evt.loaded / evt.total));
                }
            );

        }

        function uploadMultiFile(files, errFiles) {
            vm.fileUploads = files;

            angular.forEach(files, function(file) {

                var data = {
                    entity: 'PIM\\File',
                    where: {
                        name: file.name,
                        folder: vm.filter['folder'] ? vm.filter['folder'] : -1
                    }
                };


                EntityService.list(data).then(
                    function successCallback(response) {
                        var fileId = response.data.data[0].id;

                        var modalInstance = $uibModal.open({
                            templateUrl: 'views/partials/modal.html',
                            controller: 'ModalCtrl as vm',
                            resolve: {
                                title: function(){ return 'Bestehende Datei überschreiben?'; },
                                body: function(){ return 'Eine Datei mit diesem Namen ist bereits vorhanden. Wollen Sie die Datei überschreiben?'; },
                                hideCancelButton: false
                            }
                        });

                        modalInstance.result.then(
                            function (doOverwrite) {
                                if(doOverwrite){
                                    file.upload = Upload.upload({
                                        url: '/file/upload',
                                        headers: {'X-Token': localStorageService.get('token')},
                                        data: {file: file, folder: vm.filter['folder'], id: fileId}
                                    });

                                    file.upload.then(
                                        function (response) {

                                            file.result = response.data;

                                            $timeout(function () {
                                                vm.fileUploads = null;
                                                loadData();
                                            }, 1000);

                                        },
                                        function (response) {
                                            if (response.status > 0) vm.errorMsg = response.status + ': ' + response.data;
                                        },
                                        function (evt) {
                                            file.progress = Math.min(100, parseInt(100.0 * evt.loaded / evt.total));
                                        }
                                    );
                                }
                            },
                            function () {
                                vm.fileUploads = null;
                            }
                        );
                    },
                    function errorCallback(response) {
                        file.upload = Upload.upload({
                            url: '/file/upload',
                            headers: {'X-Token': localStorageService.get('token')},
                            data: {file: file, folder: vm.filter['folder']}
                        });

                        file.upload.then(
                            function (response) {

                                file.result = response.data;

                                $timeout(function () {
                                    vm.fileUploads = null;
                                    loadData();
                                }, 1000);

                            },
                            function (response) {
                                if (response.status > 0) vm.errorMsg = response.status + ': ' + response.data;
                            },
                            function (evt) {
                                file.progress = Math.min(100, parseInt(100.0 * evt.loaded / evt.total));
                            },
                            function(response){
                                console.log("error");
                            }
                        );
                    }
                );



            });

        }
    }

})();
