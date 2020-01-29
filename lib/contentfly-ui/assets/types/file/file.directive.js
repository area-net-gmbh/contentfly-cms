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
        return 'lib/contentfly-ui/assets/types/file/file.html?v=' + APP_VERSION
      },
      link: function(scope, element, attrs){

        //Properties
        scope.errorMsg          = null;
        scope.fileUpload        = {};
        scope.readable          = true;
        scope.uploadable        = true;
        scope.writable_object   = true;
        scope.writable          = true;
        scope.readonly          = false;

        //Functions
        scope.addFile       = addFile;
        scope.editFile      = editFile;
        scope.openFile      = openFile;
        scope.removeFile    = removeFile;
        scope.uploadFile    = uploadFile;


        //Startup
        init();

        /////////////////////////

        function addFile() {
          var modalInstance = $uibModal.open({
            templateUrl: 'lib/contentfly-ui/assets/views/files.html',
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
              },
              '$extend': function(){ return null;}
            },
            size: 'xl'
          });

          modalInstance.result.then(function (fileData) {
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
                templateUrl: 'lib/contentfly-ui/assets/views/partials/modal.html',
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

        function editFile(){

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
              entity: function(){ return 'PIM\\File';},
              title: function(){ return 'Objekt ' + id + ' bearbeiten'; },
              object: function(){ return scope.value; },
              lang: function(){ return null},
              translateFrom:  function(){ return null},
              doCopy: false,
              readonly: false,
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

        function openFile() {
          window.open('file/get/'+scope.value.id+'/'+scope.value.name, '_blank');
        }

        function init(){
          var permissions = localStorageService.get('permissions');
          if(!permissions){
            return;
          }

          scope.$watch('value',function(data){
            if(scope.value) scope.onChangeCallback({key: scope.key, value: scope.value.id});
          },true);

          scope.readable        = permissions['PIM\\File'].readable;
          scope.uploadable      = permissions['PIM\\File'].writable;
          scope.readonly        = parseInt(attrs.readonly) > 0;
          scope.writable_object = permissions['PIM\\File'].writable && !scope.readonly;
          scope.writable        = parseInt(attrs.writable) > 0;

        }

        function removeFile () {
          scope.value = null;
          scope.onChangeCallback({key: scope.key, value: null});
        }

        function uploadFile(file, errFiles){
          scope.fileUpload = file;

          if (file) {
            file.upload = Upload.upload({
              url: 'file/upload',
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
                file = null;
                if (response.status > 0) scope.errorMsg = 'Fehler: ' + response.status + ': ' + response.data.message;
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
