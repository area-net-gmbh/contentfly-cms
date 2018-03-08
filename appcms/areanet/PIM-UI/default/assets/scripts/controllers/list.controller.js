(function() {
    'use strict';

    angular
        .module('app')
        .controller('ListCtrl', ListCtrl);

    function ListCtrl($scope, $cookies, localStorageService, $routeParams, $http, $uibModal, pimEntity, $window, EntityService, $document, $location){

        var vm              = this;
        var oldPageNumber   = 1;


        //Properties
        vm.permissions         = localStorageService.get('permissions');
        vm.objects             = [];
        vm.objectsAvailable    = false;
        vm.objectsNotAvailable = false;

        vm.itemsPerPage  = 0;
        vm.totalItems    = 0;
        vm.currentPage   = 1;

        if(pimEntity){
            vm.entity = 'PIM\\' + $routeParams.entity;
        }else{
            vm.entity = $routeParams.entity;
        }

        vm.schema  = localStorageService.get('schema')[vm.entity];

        if(!vm.schema){
            $location.path('/');
            return;
        }

        vm.sortProperty = vm.schema.settings.sortBy;
        vm.sortOrder    = vm.schema.settings.sortOrder;
        vm.sortableOptions = {
            stop: function(e,ui){
                var resortObjectData = [];
                var sortOffset = vm.sortOrder == 'ASC'
                for(var i = 0; i < vm.objects.length; i++){
                    var newSortPosition = vm.sortOrder == 'ASC' ? i : vm.objects.length - 1 - i;
                    vm.objects[i].sorting = newSortPosition;
                    resortObjectData.push({
                        entity: vm.entity,
                        id: vm.objects[i].id,
                        data:{
                            sorting: newSortPosition
                        }
                    });
                }

                var data = {
                    objects: resortObjectData
                }

                EntityService.multiupdate(data).then(
                    function successCallback(response) {
                    },
                    function errorCallback(response) {
                    }
                );

            },
            handle: '.sortable-handle'

        };
        vm.sortableObjects = [];

        vm.filter           = {};
        vm.datalistFilter   = {};
        vm.filterIsOpen     = false;
        vm.filterBadge      = 0;
        vm.filterJoins      = {};

        //Functions
        vm.back                 = back;
        vm.closeFilter          = closeFilter;
        vm.delete               = doDelete;
        vm.executeFilter        = executeFilter;
        vm.openForm             = openForm;
        vm.paginationChanged    = paginationChanged;
        vm.redirect             = redirect;
        vm.refreshDatalistFilter= refreshDatalistFilter;
        vm.resetFilter          = resetFilter;
        vm.sortBy               = sortBy;
        vm.toggleFilter         = toggleFilter;

        //Startup
        init();
        loadData();
        loadFilters();

        ///////////////////////////////////

        function back(){
            $window.history.back();
        }

        function calcFilterBadge(){
            var badgeCount = 0;
            for (var key in vm.filter) {
                if(vm.filter[key]){
                    badgeCount++;
                }
            }

            vm.filterBadge = badgeCount;
        }


        function closeFilter(){
            vm.filterIsOpen = false;
        }

        function doDelete(object){

            if(vm.schema.settings.readonly){
                return;
            }

            var modaltitle = 'Wollen Sie den <b title="' + object.id + '">Eintrag ' + (object.id.length > 5 ? object.id.substr(0, 5) + '...' : object.id)  + '</b> wirklich löschen?';
            if(vm.schema.settings.labelProperty){
                modaltitle = 'Wollen Sie <b>' + vm.schema.settings.label + ' ' + object[vm.schema.settings.labelProperty] + '</b> wirklich löschen?';
            }

            var modalInstance = $uibModal.open({
                templateUrl: '/ui/default/views/partials/modal.html?v=' + APP_VERSION,
                controller: 'ModalCtrl as vm',
                resolve: {
                    title: function(){ return 'Eintrag löschen'; },
                    body: function(){ return modaltitle; },
                    object: function(){ return object; },
                    hideCancelButton: false
                }
            });

            modalInstance.result.then(
                function (doDelete) {


                    if(doDelete){

                        vm.objectsAvailable = false;
                        vm.objectsNotAvailable = false;

                        var data = {
                            entity: vm.entity,
                            id: object.id
                        };

                        EntityService.delete(data).then(
                            function successCallback(response) {
                                loadData();
                            },
                            function errorCallback(response) {
                                if(response.status == 401){
                                    var modalInstance = $uibModal.open({
                                        templateUrl: '/ui/default/views/partials/relogin.html?v=' + APP_VERSION,
                                        controller: 'ReloginCtrl as vm',
                                        backdrop: 'static'
                                    });

                                    modalInstance.result.then(
                                        function () {
                                            vm.delete(object);
                                        },
                                        function () {
                                            $uibModalInstance.close();
                                            $location.path('/logout');
                                        }
                                    );

                                }else{
                                    var modalInstance = $uibModal.open({
                                        templateUrl: '/ui/default/views/partials/modal.html?v=' + APP_VERSION,
                                        controller: 'ModalCtrl as vm',
                                        resolve: {
                                            title: function(){ return 'Fehler beim Löschen'; },
                                            body: function(){ return response.data.message; },
                                            hideCancelButton: true
                                        }
                                    });

                                }
                            }
                        );
                    }
                },
                function () {

                }
            );
        }

        function executeFilter() {

            var savedDatalistFilter = localStorageService.get('savedDatalistFilter');
            if(!savedDatalistFilter){
                savedDatalistFilter = {};
            }
            savedDatalistFilter[vm.entity] = vm.datalistFilter;
            localStorageService.set('savedDatalistFilter', savedDatalistFilter);

            for (var key in vm.datalistFilter) {
                vm.filter[key] = vm.datalistFilter[key].id
            }

            var savedFilter = localStorageService.get('savedFilter');
            if(!savedFilter){
                savedFilter = {};
            }
            savedFilter[vm.entity] = vm.filter;
            localStorageService.set('savedFilter', savedFilter);

            calcFilterBadge();
            loadData();
        }

        function generateTree(entity, field, data, depth){
            var joinSchema = localStorageService.get('schema')[entity];

            vm.filterJoins[field] = vm.filterJoins[field] ? vm.filterJoins[field] : [];

            for(var i = 0; i < data.length; i++){
                var filler = '--'.repeat(depth);
                filler = filler ? filler + ' ' : filler

                if(joinSchema.settings.labelProperty){
                    data[i]['pim_filterTitle'] = filler + data[i][joinSchema.settings.labelProperty];
                }else{
                    data[i]['pim_filterTitle'] = filler + data[i][joinSchema.list[Object.keys(joinSchema.list)[0]]];
                }

                vm.filterJoins[field].push(data[i]);
                if(data[i]['treeChilds']){
                    var subDepth = depth + 1;
                    generateTree(entity, field, data[i]['treeChilds'], subDepth);
                }
            }
        }

        function init(){
            var savedFilter = localStorageService.get('savedFilter');
            if(savedFilter && savedFilter[vm.entity]){
                vm.filter = savedFilter[vm.entity];
            }

            var savedDatalistFilter = localStorageService.get('savedDatalistFilter');
            if(savedDatalistFilter && savedDatalistFilter[vm.entity]){
                vm.datalistFilter = savedDatalistFilter[vm.entity];
            }

            for (var key in $routeParams) {
                if (key.substr(0, 2) != 'f_') {
                    continue;
                }
                var property = key.substr(2);

                vm.filter[property] = $routeParams[key];
            }

            calcFilterBadge();
        }

        function loadData(){
            vm.objectsAvailable = false;
            vm.objectsNotAvailable = false;

            var sortSettings = {};
            sortSettings[vm.sortProperty] = vm.sortOrder;

            var properties = ['id'];

            for (key in vm.schema.list ) {
                properties.push(vm.schema.list[key]);
            }

            if(vm.schema.settings.type == 'tree' || vm.schema.settings.sortRestrictTo){
                vm.schema.settings.isSortable = false;
            }

            var filter = {};
            for (var key in vm.filter) {
                if(vm.filter[key]){
                    filter[key] = vm.filter[key];
                    if(vm.schema.settings.type == 'tree' && key == 'treeParent' && vm.filter[key] ){
                        vm.schema.settings.isSortable = true;
                    }
                    if(vm.schema.settings.sortRestrictTo && key == vm.schema.settings.sortRestrictTo && vm.filter[key] ){
                        vm.schema.settings.isSortable = true;
                    }

                    if(vm.schema.properties[key] && vm.schema.properties[key].type == 'boolean'){
                        filter[key] = vm.filter[key] == 'true' || vm.filter[key] == '1' ? true : false;
                    }
                }
            }

            if(vm.schema.settings.isSortable){
                properties.push('sorting');
            }

            var data = {
                entity: vm.entity,
                currentPage: vm.schema.settings.isSortable ? 0 : vm.currentPage,
                order: sortSettings,
                where: filter,
                properties: properties,
                flatten:false
            };
            EntityService.list(data).then(
                function successCallback(response) {

                    if(vm.itemsPerPage === 0) {
                        vm.itemsPerPage = response.data.itemsPerPage;
                    }

                    vm.totalItems = response.data.totalItems;
                    vm.objects = response.data.data;

                    vm.objectsAvailable = true;
                    vm.objectsNotAvailable = false;
                },
                function errorCallback(response) {
                    vm.objectsAvailable = false;
                    vm.objectsNotAvailable = true;

                    if(response.status == 401){
                        var modalInstance = $uibModal.open({
                            templateUrl: '/ui/default/views/partials/relogin.html?v=' + APP_VERSION,
                            controller: 'ReloginCtrl as vm',
                            backdrop: 'static'
                        });

                        modalInstance.result.then(
                            function () {
                                loadData();
                            },
                            function () {
                                $uibModalInstance.close();
                                $location.path('/logout');
                            }
                        );
                    }

                    if(response.status == 403){
                        $location.path('/error');
                    }
                }
            );
        }

        function loadFilters(){
            for (var key in vm.schema.properties) {
                if(vm.schema.properties[key].type == 'join' && vm.schema.properties[key].isFilterable ){

                    var entity = null;
                    if (vm.schema.properties[key].accept.substr(0, 7) == 'Areanet') {
                        entity = vm.schema.properties[key].accept.replace('Areanet\\PIM\\Entity\\', 'PIM\\');
                    } else {
                        entity = vm.schema.properties[key].accept.replace('Custom\\Entity\\', '').replace('\\', '');
                    }

                    if (!vm.permissions[entity].readable) {
                        continue;
                    }

                    if(vm.schema.properties[key].isDatalist && localStorageService.get('schema')[entity].settings.type != 'tree'){
                        continue;
                    }

                    var joinSchema = localStorageService.get('schema')[entity];

                    if (localStorageService.get('schema')[entity].settings.type == 'tree') {

                        var properties = ['id'];
                        if (joinSchema.settings.isSortable) {
                            properties.push('sorting');
                        }
                        for (var key2 in joinSchema.list) {
                            properties.push(joinSchema.list[key2]);
                        }

                        EntityService.tree(
                            {entity: entity, properties: properties}
                        ).then(
                            (function (entity, key) {
                                return function (response) {
                                    generateTree(entity, key, response.data.data, 0)
                                }
                            })(entity, key),
                            function errorCallback(response) {
                            }
                        );
                    } else {


                        var properties = ['id'];
                        if (joinSchema.settings.isSortable) {
                            properties.push('sorting');
                        }
                        for (var key2 in joinSchema.list) {
                            properties.push(joinSchema.list[key2]);
                        }

                        EntityService.list({entity: entity, properties: properties, flatten: true}).then(
                            (function (entity, key, joinSchema) {
                                return function (response) {
                                    vm.filterJoins[key] = response.data.data;
                                    for (var i = 0; i < vm.filterJoins[key].length; i++) {
                                        if (!vm.filterJoins[key][i]['pim_filterTitle']) {
                                            if (joinSchema.settings.labelProperty) {
                                                vm.filterJoins[key][i]['pim_filterTitle'] = vm.filterJoins[key][i][joinSchema.settings.labelProperty];
                                            } else {
                                                vm.filterJoins[key][i]['pim_filterTitle'] = vm.filterJoins[key][i][joinSchema.list[Object.keys(joinSchema.list)[0]]];
                                            }
                                        }
                                    }
                                }
                            })(entity, key, joinSchema),
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

                    if(!vm.permissions[entity].readable){
                        continue;
                    }

                    if(vm.schema.properties[key].isDatalist && localStorageService.get('schema')[entity].settings.type != 'tree'){
                        continue;
                    }

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

                        EntityService.list({entity: entity, flatten: true}).then(
                            (function(entity, key) {
                                return function(response) {
                                    var joinSchema = localStorageService.get('schema')[entity];
                                    vm.filterJoins[key] = response.data.data;

                                    for(var i = 0; i < vm.filterJoins[key].length; i++){
                                        if(!vm.filterJoins[key][i]['pim_filterTitle']){
                                            if(joinSchema.settings.labelProperty){
                                                vm.filterJoins[key][i]['pim_filterTitle'] = vm.filterJoins[key][i][joinSchema.settings.labelProperty];
                                            }else{
                                                vm.filterJoins[key][i]['pim_filterTitle'] = vm.filterJoins[key][i][joinSchema.list[Object.keys(joinSchema.list)[0]]];
                                            }
                                        }
                                    }
                                }
                            })(entity, key),
                            function errorCallback(response) {
                            }
                        );
                    }


                }else if(vm.schema.properties[key].type == 'virtualjoin' && vm.schema.properties[key].isFilterable){

                    var entity = null;
                    if(vm.schema.properties[key].accept.substr(0,7) == 'Areanet'){
                        entity = vm.schema.properties[key].accept.replace('Areanet\\PIM\\Entity\\', 'PIM\\');
                    }else{
                        entity =  vm.schema.properties[key].accept.replace('Custom\\Entity\\', '').replace('\\', '');
                    }

                    if(!vm.permissions[entity].readable){
                        continue;
                    }

                    EntityService.list({entity: entity, flatten: true}).then(
                        (function(entity, key) {
                            return function(response) {
                                var joinSchema = localStorageService.get('schema')[entity];
                                vm.filterJoins[key] = response.data.data;
                                for(var i = 0; i < vm.filterJoins[key].length; i++){
                                    if(!vm.filterJoins[key][i]['pim_filterTitle']){
                                        if(joinSchema.settings.labelProperty){
                                            vm.filterJoins[key][i]['pim_filterTitle'] = vm.filterJoins[key][i][joinSchema.settings.labelProperty];
                                        }else{
                                            vm.filterJoins[key][i]['pim_filterTitle'] = vm.filterJoins[key][i][joinSchema.list[Object.keys(joinSchema.list)[0]]];
                                        }
                                    }
                                }
                            }
                        })(entity, key),
                        function errorCallback(response) {
                        }
                    );



                }else if(vm.schema.properties[key].isFilterable){
                    var field = key;

                    var data = {
                        entity: vm.entity,
                        properties: [field],
                        groupBy: field
                    };

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

        function openForm(object, index, readonly){

            if(vm.schema.settings.readonly){
                return;
            }
            var doInsert    = false;
            var modaltitle  = 'Neues Objekt anlegen';
            var prefixTitle = readonly != 1 ? ' bearbeiten' : ' ansehen';
            if(object && vm.schema.settings.labelProperty){
                modaltitle = vm.schema.settings.label + ' ' + (object[vm.schema.settings.labelProperty] ? object[vm.schema.settings.labelProperty] : 'ID' + object.id) + prefixTitle;
            }else if(object){
                modaltitle = '<span title="' + object.id + '">Objekt ' + (object.id.length > 5 ? object.id.substr(0, 5) + '...' : object.id) + prefixTitle + '</span>';
            }else{
                doInsert = true;
                object = {};

                for (var key in vm.schema.properties) {
                    if(!vm.schema.properties[key].type == 'join' && !vm.filter[key]){
                        continue;
                    }

                    object[key] = vm.filter[key]
                }

            }

            var modalInstance = $uibModal.open({
                templateUrl: '/ui/default/views/form.html?v=' + APP_VERSION,
                controller: 'FormCtrl as vm',
                resolve: {
                    entity: function(){ return vm.entity;},
                    title: function(){ return modaltitle; },
                    object: function(){ return object; },
                    readonly: readonly != 1 ? false : true
                },
                backdrop: 'static',
                size: 'xl'
            });


            modalInstance.result.then(
                function (updatedObject) {
                    if(doInsert){
                      loadData();
                    }else{
                      vm.objects[index] = updatedObject;
                    }

                },
                function () {
                }
            );


        }

        function paginationChanged(newPageNumber) {
            if(oldPageNumber == newPageNumber){
                return;
            }
            vm.objectsAvailable = false;
            vm.currentPage = newPageNumber;
            oldPageNumber = newPageNumber;

            loadData();
        }

        function redirect(path){
            $location.url(path);
        }

        function refreshDatalistFilter(key, sWord){

            var entity = null;
            if (vm.schema.properties[key].accept.substr(0, 7) == 'Areanet') {
                entity = vm.schema.properties[key].accept.replace('Areanet\\PIM\\Entity\\', 'PIM\\');
            } else {
                entity = vm.schema.properties[key].accept.replace('Custom\\Entity\\', '').replace('\\', '');
            }

            if (!vm.permissions[entity].readable) {
                return;
            }


            var joinSchema = localStorageService.get('schema')[entity];

            var properties = ['id'];
            if (joinSchema.settings.isSortable) {
                properties.push('sorting');
            }
            for (var key2 in joinSchema.list) {
                properties.push(joinSchema.list[key2]);
            }

            var data = {
                entity: entity,
                properties: properties,
                flatten: true
            };

            if(vm.schema.properties[key].isDatalist){
                data.itemsPerPage = 15;
                data.currentPage = 1;
            }

            if(sWord){
                data.where = {fulltext: sWord};
            }

            EntityService.list(data).then(
                (function (entity, key, joinSchema) {
                    return function (response) {
                        if(sWord || !vm.schema.properties[key].isDatalist){
                            vm.filterJoins[key] = [];
                        }else{
                            vm.filterJoins[key] = [
                                {
                                    id: 0,
                                    pim_filterTitle: 'alle anzeigen',
                                    group: 'Allgemein'
                                },
                                {
                                    id: -1,
                                    pim_filterTitle: 'Ohne Zuordnung',
                                    group: 'Allgemein'
                                }
                            ];
                        }

                        for (var i = 0; i < response.data.data.length; i++) {

                            response.data.data[i]['group'] = 'Objekte';
                            if (!response.data.data[i]['pim_filterTitle']) {
                                if (joinSchema.settings.labelProperty) {
                                    response.data.data[i]['pim_filterTitle'] = response.data.data[i][joinSchema.settings.labelProperty];
                                } else {
                                    response.data.data[i]['pim_filterTitle'] = response.data.data[i][joinSchema.list[Object.keys(joinSchema.list)[0]]];
                                }
                            }
                        }

                        vm.filterJoins[key] = vm.filterJoins[key].concat(response.data.data);
                    }
                })(entity, key, joinSchema),
                function errorCallback(response) {
                }
            );
        }

        function resetFilter() {
            vm.filter = {};
            vm.filterBadge = 0;
            vm.filterIsOpen = false;
            vm.datalistFilter = {};

            localStorageService.set('savedDatalistFilter', vm.datalistFilter);
            localStorageService.set('savedFilter', vm.filter);

            loadData();
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

        function toggleFilter(open){
            if(open){
                $document.find('#fulltext').focus();

            }
        }



    }

})();
