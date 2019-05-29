(function() {
  'use strict';

  angular
    .module('app')
    .directive('pimVirtualjoin', pimVirtualjoin);


  function pimVirtualjoin($uibModal, $timeout, EntityService, localStorageService, $rootScope){
    return {
      restrict: 'E',
      scope: {
        key: '=', config: '=', object: '=', value: '=', isValid: '=',  isSubmit: '=', onChangeCallback: '&'
      },
      templateUrl: function(){
        return '/ui/default/types/virtualjoin/virtualjoin.html?v=' + APP_VERSION
      },
      link: function(scope, element, attrs){
        var itemsPerPage = 10;
        var entity       = null;

        scope.$watch('value',function(data){

          if(!scope.value){
            scope.choosenIds   = [];
            scope.value        = [];
          }else{
            scope.choosenIds   = [];
            for(var i = 0; i < scope.value.length; i++){
              scope.choosenIds.push(scope.value[i].id);
              if(!scope.value[i].created) {

                var data = {
                  entity: scope.config.accept,
                  id: scope.value[i].id,
                  lang: scope.object.lang
                };

                EntityService.single(data).then(
                  (function (i) {
                    return function (response) {
                      scope.value[i] = response.data.data;
                      triggerUpdate();
                    }
                  })(i),
                  function (data) {

                  }
                );

              }

            }
          }

        },true);



        //Properties
        scope.choosenIds    = [];
        scope.chooserOpened = false;
        scope.currentPage   = 1;
        scope.deletable     = false;
        scope.hide          = false;
        scope.propertyCount = 0;
        scope.objects       = [];
        scope.readonly      = false;
        scope.schema        = null;
        scope.selectedIndex = 0;
        scope.sortableOptions = {
          stop: function(e,ui){
            triggerUpdate();
          },
          disabled: !scope.config.sortable
        };
        scope.totalPages    = 1;
        scope.value = scope.value ? scope.value : [];
        scope.writable = false;
        scope.writable_object= false;

        //Functions
        scope.addNewObject  = addNewObject;
        scope.change        = change;
        scope.chooseObject  = chooseObject;
        scope.closeChooser  = closeChooser;
        scope.disableObject = disableObject;
        scope.editObject    = editObject;
        scope.keyPressed    = keyPressed;
        scope.loadData      = loadData;
        scope.openChooser   = openChooser;
        scope.removeObject  = removeObject;

        //Startup
        init();

        /////////////////////////////////////

        function addNewObject(){

          var templateUrl = '/ui/default/views/form.html?v=' + APP_VERSION;
          var controller  = 'FormCtrl as vm';

          if(extendedRoutes['form']){
            templateUrl =  extendedRoutes['form'][0]['template'];
            controller  = extendedRoutes['form'][0]['controller'] + ' as vm';
          }

          var modalInstance = $uibModal.open({
            templateUrl: templateUrl,
            controller: controller,
            resolve: {
              entity: function(){ return entity;},
              title: function(){ return 'Neues Objekt anlegen'; },
              object: function(){ return null; },
              readonly: false,
              lang: function(){ return scope.object.lang;},
              doCopy: false,
              translateFrom:  function(){ null;},
              '$extend': function(){ return null;}
            },
            backdrop: 'static',
            size: 'xl'
          });

          modalInstance.result.then(
            function (newObject) {
              if(newObject){
                if(scope.chooserOpened) scope.objects.push(newObject);
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

          if(scope.choosenIds.indexOf(object.id) > -1){
            return;
          }

          var newData = {};

          if(scope.config.mappedBy){
            newData[scope.config.mappedBy] =  object;
            if(scope.config.sortable){
              newData['isActive'] = true;
            }
          }else{
            newData = object;
          }

          scope.value.push(newData);

          triggerUpdate();

        }

        function closeChooser(){
          scope.chooserOpened = false;
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

        function editObject(index){

          var id     = scope.config.mappedBy ? scope.value[index][scope.config.mappedBy].id : scope.value[index].id;
          var object = scope.config.mappedBy ? scope.value[index][scope.config.mappedBy] : scope.value[index];

          var templateUrl = '/ui/default/views/form.html?v=' + APP_VERSION;
          var controller  = 'FormCtrl as vm';

          if(extendedRoutes['form']){
            templateUrl =  extendedRoutes['form'][0]['template'];
            controller  = extendedRoutes['form'][0]['controller'] + ' as vm';
          }

          var modalInstance = $uibModal.open({
            templateUrl: templateUrl,
            controller: controller,
            resolve: {
              entity: function(){ return entity;},
              object: function(){ return object; },
              readonly: false,
              lang: function(){ return scope.object.lang;},
              doCopy: false,
              translateFrom:  function(){ null;},
              '$extend': function(){ return null;}
            },
            backdrop: 'static',
            size: 'xl'
          });

          modalInstance.result.then(
            function (newObject) {
              if(newObject){
                if(scope.config.mappedBy){
                  scope.value[index][scope.config.mappedBy] = newObject;
                }else{
                  scope.value[index] = newObject;
                }

              }
            },
            function () {}
          );
        }

        function init(){
          entity = $rootScope.getShortEntityName(scope.config.accept);

          var permissions = localStorageService.get('permissions');
          if(!permissions){
            return;
          }

          if(scope.config.acceptFrom){
            var entityForm = $rootScope.getShortEntityName(scope.config.acceptFrom);

            scope.hide = !permissions[entity].readable || !permissions[entityForm].readable;

            scope.deletable         = permissions[entityForm].deletable;
            scope.writable_object   = permissions[entity].writable;
            scope.writable          = parseInt(attrs.writable) > 0 && permissions[entityForm].writable > 0;
          }else{
            scope.hide              = !permissions[entity].readable;
            scope.writable_object   = permissions[entity].writable;
            scope.deletable         = parseInt(attrs.writable) > 0;
            scope.writable          = parseInt(attrs.writable) > 0;
          }

          scope.schema = localStorageService.get('schema')[entity];

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

        function removeObject(index){
          scope.value.splice(index, 1);

          triggerUpdate();
        }

        function triggerUpdate(){
          var values = [];

          if(scope.config.mappedBy){
            for (var index in scope.value) {
              if(scope.value[index][scope.config.mappedBy]) values.push(scope.value[index][scope.config.mappedBy].id);
            }
          }else{
            for (var index in scope.value) {
              values.push(scope.value[index].id);
            }
          }

          scope.onChangeCallback({key: scope.key, value: values});
        }
      }
    }
  }

})();
