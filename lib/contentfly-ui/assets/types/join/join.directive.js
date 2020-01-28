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
        return 'lib/contentfly-ui/assets/types/join/join.html?v=' + APP_VERSION
      },
      link: function(scope, element, attrs){
        var itemsPerPage = 10;

        //Properties
        scope.chooserOpened     = false;
        scope.entity            = null;
        scope.currentPage       = 1;
        scope.deletable         = false;
        scope.hide              = false;
        scope.propertyCount     = 0;
        scope.isTree            = false;
        scope.objects           = [];
        scope.readonly          = false;
        scope.schema            = null;
        scope.selectedIndex     = 0;
        scope.totalPages        = 1;
        scope.writable          = false;
        scope.writable_object   = false;

        //Functions
        scope.addNewObject      = addNewObject;
        scope.change            = change;
        scope.chooseObject      = chooseObject;
        scope.closeChooser      = closeChooser;
        scope.editObject        = editObject;
        scope.keyPressed        = keyPressed;
        scope.loadData          = loadData;
        scope.openChooser       = openChooser;
        scope.removeObject      = removeObject;
        scope.setSelectedIndex  = setSelectedIndex;

        //Startup
        init();

        /////////////////////////////////////

        function addNewObject(){

          var templateUrl = 'lib/contentfly-ui/assets/views/form.html?v=' + APP_VERSION;
          var controller  = 'FormCtrl as vm';

          if(extendedRoutes['form']){
            templateUrl =  extendedRoutes['form'][0]['template'] ? extendedRoutes['form'][0]['template'] : templateUrl;
            controller  =  extendedRoutes['form'][0]['controller'] ? extendedRoutes['form'][0]['controller'] + ' as vm' : controller;
          }

          var modalInstance = $uibModal.open({
            templateUrl: templateUrl,
            controller: controller,
            resolve: {
              entity: function(){ return scope.entity;},
              title: function(){ return 'Neues Objekt anlegen'; },
              object: function(){ return null; },
              readonly: false,
              lang: function(){ return scope.object.lang},
              doCopy: false,
              translateFrom:  function(){ null},
              '$extend': function(){ return null;}
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
          if(!scope.isTree){
            scope.currentPage = 1;
            loadData();
          }

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
          var templateUrl = 'lib/contentfly-ui/assets/views/form.html?v=' + APP_VERSION;
          var controller  = 'FormCtrl as vm';

          if(extendedRoutes['form']){
            templateUrl =  extendedRoutes['form'][0]['template'] ? extendedRoutes['form'][0]['template'] : templateUrl;
            controller  =  extendedRoutes['form'][0]['controller'] ? extendedRoutes['form'][0]['controller'] + ' as vm' : controller;
          }

          var modalInstance = $uibModal.open({
            templateUrl: templateUrl,
            controller: controller,
            resolve: {
              entity: function(){ return scope.entity;},
              object: function(){ return scope.value; },
              readonly: false,
              lang: function(){ return scope.object.lang},
              doCopy: false,
              translateFrom:  function(){ null},
              '$extend': function(){ return null;}
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

          scope.entity = $rootScope.getShortEntityName(scope.config.accept);

          var permissions = localStorageService.get('permissions');
          if(!permissions){
            return;
          }

          if(scope.value){


            if(!isObject(scope.value)) {

              var data = {
                entity: scope.entity,
                lang:scope.object.lang,
                id: scope.value
              };

              EntityService.single(data).then(
                function (response) {
                  scope.value = response.data.data;
                  scope.onChangeCallback({key: scope.key, value: scope.value.id});
                },
                function (data) {

                  if (data.status == 401) {
                    var modalInstance = $uibModal.open({
                      templateUrl: 'lib/contentfly-ui/assets/views/partials/relogin.html?v=' + APP_VERSION,
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
                      templateUrl: 'lib/contentfly-ui/assets/views/partials/modal.html?v=' + APP_VERSION,
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

          scope.hide              = !permissions[scope.entity].readable;
          scope.readonly          = parseInt(attrs.readonly) > 0;
          scope.writable_object   = permissions[scope.entity].writable && !scope.readonly;
          scope.writable          = parseInt(attrs.writable) > 0;

          scope.schema    = localStorageService.get('schema')[scope.entity];

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

          scope.isTree = scope.schema.settings.type == 'tree';

          if(scope.isTree){
            var filterData = {
              entity: scope.entity,
              lang: scope.object.lang
            };

            EntityService.tree2(filterData).then(
              function (response) {
                  scope.objects = response.data.data;
              },
              function errorCallback(response) {
              }
            );
          }else {

            var currentPage = scope.currentPage;
            var sortSettings = {};
            sortSettings[scope.schema.settings.sortBy] = scope.schema.settings.sortOrder;

            var data = {
              entity: scope.entity,
              currentPage: currentPage,
              itemsPerPage: itemsPerPage,
              order: sortSettings,
              where: where,
              properties: properties,
              lang: scope.object.lang
            };
            EntityService.list(data).then(
              function successCallback(response) {
                scope.totalPages = Math.ceil(response.data.totalItems / itemsPerPage);

                var data = [];
                data = response.data.data

                scope.objects = data;
                scope.selectedIndex = 0;
              },
              function errorCallback(response) {
                scope.objects = [];
              }
            );
          }
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
