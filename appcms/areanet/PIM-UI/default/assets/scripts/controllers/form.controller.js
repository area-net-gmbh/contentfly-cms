(function () {
  'use strict';

  angular
    .module('app')
    .controller('FormCtrl', FormCtrl);

  function FormCtrl($scope, $cookies, $uibModalInstance, $location, localStorageService, $timeout, $uibModal, $http, entity, object, doCopy, lang, translateFrom, Upload, moment, EntityService, FileService, readonly, $extend) {
    var vm  = $extend ? $extend : this;

    var schemaComplete = localStorageService.get('schema');
    var objectDataToSave = {};
    var backupForObject = null;
    var refreshOnCancel = false;

    //Properties
    vm.doCopy = doCopy;
    vm.doSave = false;
    vm.entity = entity;
    vm.schemaOnejoin = {};
    vm.schema = schemaComplete[entity];

    vm.object = {};
    vm.isLoading = true;
    vm.isSubmit = false;

    vm.readonly = readonly;
    vm.fileUploads = {};
    vm.forms = {};
    vm.modaltitle1 = '';
    vm.modaltitle2 = '';
    vm.password = {};
    vm.permissions = localStorageService.get('permissions');

    //Functions
    vm.save = save;
    vm.cancel = cancel;
    vm.changeValue = onChangeValue;
    vm.delete = doDelete;

    //Startup
    init();

    ///////////////////////////////////

    function cancel() {
      if (refreshOnCancel) {
        $uibModalInstance.close(vm.object);
      } else {
        $uibModalInstance.dismiss(false);
      }

    }

    function confirmPush(count, title, text, object) {
      var modalInstance = $uibModal.open({
        templateUrl: '/ui/default/views/partials/modal.html?v=' + APP_VERSION,
        controller: 'ModalCtrl as vm',
        resolve: {
          title: function () {
            return 'Push-Nachricht an ' + count + ' Benutzer versenden';
          },
          body: function () {
            return "<p><b>" + title + "</b></p><p>" + text + "</p><p><br>" + object + "</p>";
          },
          hideCancelButton: function () {
            return false;
          }
        }
      });

      modalInstance.result.then(
        function (doDelete) {
          if (doDelete) doSave(true);
        },
        function () {
        }
      );
    }

    function doDelete(object) {

      if (vm.schema.settings.readonly) {
        return;
      }

      var modaltitle = 'Wollen Sie den <b title="' + object.id + '">Eintrag ' + (object.id.length > 5 ? object.id.substr(0, 5) + '...' : object.id) + '</b> wirklich löschen?';
      if (vm.schema.settings.labelProperty) {
        modaltitle = 'Wollen Sie <b>' + vm.schema.settings.label + ' ' + object[vm.schema.settings.labelProperty] + '</b> wirklich löschen?';
      }

      var modalInstance = $uibModal.open({
        templateUrl: '/ui/default/views/partials/modal.html?v=' + APP_VERSION,
        controller: 'ModalCtrl as vm',
        resolve: {
          title: function () {
            return 'Eintrag löschen';
          },
          body: function () {
            return modaltitle;
          },
          object: function () {
            return object;
          },
          hideCancelButton: false
        }
      });

      modalInstance.result.then(
        function (doDelete) {


          if (doDelete) {

            vm.objectsAvailable = false;
            vm.objectsNotAvailable = false;

            var data = {
              entity: vm.entity,
              id: object.id
            };

            EntityService.delete(data).then(
              function successCallback(response) {
                $uibModalInstance.close(vm.object);
              },
              function errorCallback(response) {
                if (response.status == 401) {
                  var modalInstance = $uibModal.open({
                    templateUrl: '/ui/default/views/partials/relogin.html?v=' + APP_VERSION,
                    controller: 'ReloginCtrl as vm',
                    backdrop: 'static'
                  });

                  modalInstance.result.then(
                    function () {
                      $uibModalInstance.close(false);
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
                        return 'Fehler beim Löschen';
                      },
                      body: function () {
                        return response.data.message;
                      },
                      hideCancelButton: true
                    }
                  });
                  $uibModalInstance.close(false);
                }
              }
            );
          }
        },
        function () {

        }
      );
    }

    function doSave(andClose) {
      vm.isSubmit = true;
      refreshOnCancel = true;

      for (var formName in vm.forms) {

        if (!vm.forms[formName].$valid) {
          return;
        }
      }

      vm.doSave = true;

      if(entity == 'PIM\\File' && objectDataToSave['type']=='link/youtube'){
        objectDataToSave['hash'] = objectDataToSave['name'];
        objectDataToSave['size'] = 0;
      }

      if (!vm.object['id'] || translateFrom) {

        var data = {
          entity: entity,
          data: objectDataToSave,
          lang: lang
        };




        EntityService.insert(data).then(
          function successCallback(response) {
            vm.doSave = false;
            vm.object.id = response.data.id;
            if (andClose) {
              if (translateFrom) {
                $uibModalInstance.close();
              } else {
                $uibModalInstance.close(response.data.data);
              }

            }
          },
          function errorCallback(response) {

            vm.doSave = false;

            if (response.status == 401) {
              var modalInstance = $uibModal.open({
                templateUrl: '/ui/default/views/partials/relogin.html?v=' + APP_VERSION,
                controller: 'ReloginCtrl as vm',
                backdrop: 'static'
              });

              modalInstance.result.then(
                function () {
                  if (translateFrom) {
                    doSave();
                  } else {
                    doSave(vm.object);
                  }

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
                    return 'Fehler beim Anlegen des Datensatzes';
                  },
                  body: function () {
                    return response.data.message;
                  },
                  hideCancelButton: true
                }
              });
            }
          }
        );
      } else {

        var data = {
          entity: entity,
          id: vm.object['id'],
          lang: lang,
          data: objectDataToSave
        };


        EntityService.update(data).then(
          function successCallback(response) {
            vm.doSave = false;
            if (andClose) $uibModalInstance.close(vm.object);
          },
          function errorCallback(response) {
            vm.doSave = false;

            if (response.status == 401) {
              var modalInstance = $uibModal.open({
                templateUrl: '/ui/default/views/partials/relogin.html?v=' + APP_VERSION,
                controller: 'ReloginCtrl as vm',
                backdrop: 'static'
              });

              modalInstance.result.then(
                function () {
                  doSave(vm.object);
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
                    return response.data.type == 'Areanet\\PIM\\Classes\\Exceptions\\File\\FileExistsException' ? 'Datei überschreiben?' : 'Fehler beim Anlegen des Datensatzes';
                  },
                  body: function () {
                    return response.data.message;
                  },
                  hideCancelButton: true
                }
              });

              if (response.data.type == 'Areanet\\PIM\\Classes\\Exceptions\\File\\FileExistsException') {
                modalInstance.result.then(
                  function (doOverwrite) {
                    if (doOverwrite) {
                      FileService.overwrite(vm.object['id'], response.data.file_id).then(
                        function successCallback(response) {
                        },
                        function errorCallback(response) {
                        }
                      );
                      $uibModalInstance.close(vm.object);
                    }
                  },
                  function () {
                  }
                );
              }
            }
          }
        );
      }
    }

    function viewStateFor(key, config){

      if(!config.if) return false;

      if(!config.if.property || !config.if.equals) return false;

      var prop = objectDataToSave[config.if.property] ? objectDataToSave[config.if.property] : vm.object[config.if.property];

      if(!prop) return false;

      if(prop.toLowerCase() == config.if.equals.toLowerCase()){
        vm.schema.properties[key].hide      = config.if.hide;
        vm.schema.properties[key].readonly  = config.if.readonly;
        vm.schema.properties[key].label     = config.if.label ? config.if.label : vm.schema.properties[key].org.label;
      }else{
        vm.schema.properties[key].readonly  = vm.schema.properties[key].org.readonly;
        vm.schema.properties[key].hide      = vm.schema.properties[key].org.hide;
        vm.schema.properties[key].label      = vm.schema.properties[key].org.label;
      }


    }

    function parseViewState(){
      angular.forEach(vm.schema.properties, function (config, key) {


        viewStateFor(key, config);

      });
    }

    function init() {

      if (object) {
        vm.object = object;
      }


      if (object && object.id && !vm.doCopy) {
        vm.modaltitle1 = vm.schema.settings.label + (vm.readonly ? ' ansehen    ' : ' bearbeiten');
        if (vm.schema.settings.labelProperty && object[vm.schema.settings.labelProperty]) {
          vm.modaltitle2 = object[vm.schema.settings.labelProperty];
        } else {
          vm.modaltitle2 = object.id;
        }

        if (translateFrom) {
          vm.object.lang = lang;
        }
      } else {
        vm.modaltitle1 = '';
        vm.modaltitle2 = vm.schema.settings.label + (vm.doCopy ? ' kopieren' : ' anlegen');

        if (lang) {
          vm.object.lang = lang;
        }

        if (vm.doCopy && vm.schema.settings.labelProperty && object[vm.schema.settings.labelProperty]) {
          object[vm.schema.settings.labelProperty] = object[vm.schema.settings.labelProperty] + ' (Kopie)';
        }

      }

      var i18nPermissions = localStorageService.get('i18nPermissions');

      angular.forEach(vm.schema.properties, function (config, key) {
        vm.schema.properties[key].org = {
          readonly : vm.schema.properties[key].readonly,
          hide : vm.schema.properties[key].hide,
          label : vm.schema.properties[key].label
        };

        if (config.type == 'onejoin') {
          vm.schemaOnejoin[config.tab] = schemaComplete[config.tab];
          vm.schemaOnejoin[config.tab].properties.id['hide'] = true;
          vm.schemaOnejoin[config.tab].properties.userCreated['hide'] = true;
          vm.schemaOnejoin[config.tab].properties.users['hide'] = true;
          vm.schemaOnejoin[config.tab].properties.groups['hide'] = true;
          vm.object[key] = vm.object[key] ? vm.object[key] : {};
        }

        if (vm.schema.settings.i18n && config.i18n_universal && i18nPermissions && i18nPermissions[lang] && i18nPermissions[lang] == 'translatable') {
          if (!vm.permissions[vm.entity].extended) {
            vm.permissions[vm.entity].extended = {fieldPermission: {}};
          }

          vm.permissions[vm.entity].extended.fieldPermission[key] = 1;
        }


        viewStateFor(key, config);



      });

      if (!object || !object.id || !vm.permissions[entity].readable) {
        vm.isLoading = false;
        cancel();
        return;
      }

      var frontend = localStorageService.get('frontend');
      var currentLang = frontend.languages ? frontend.languages[0] : null

      var data = {
        entity: entity,
        id: object.id,
        lang: translateFrom ? translateFrom : lang,
        compareToLang: translateFrom ? lang : currentLang,
        loadJoinedLang: translateFrom ? lang : null
      };

      EntityService.single(data).then(
        function (response) {

          if (vm.doCopy && vm.schema.settings.labelProperty && response.data.data[vm.schema.settings.labelProperty] && vm.object[vm.schema.settings.labelProperty]) {
            response.data.data[vm.schema.settings.labelProperty] = vm.object[vm.schema.settings.labelProperty];
          }

          vm.object = response.data.data;

          if (vm.doCopy) {
            vm.object.id = null;
          }

          if (translateFrom) {
            vm.object.lang = lang;
          }

          vm.isLoading = false;
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
                  return data.status == 550 ? 'Fehlende Übersetzungen' : data.statusText;
                },
                body: function () {
                  var text = data.data.message;

                  if (data.data.message_value) {
                    text += ' (' + data.data.message_value + ')';
                  }

                  if (data.data.message_entity) {
                    text += ' (' + data.data.message_entity + ')';
                  }

                  return text;
                },
                hideCancelButton: function () {
                  return false;
                }
              }
            });

            modalInstance.result.then(
              function (doDelete) {
                if (data.status == 550) {
                  $location.path('/list/' + data.data.message_entity).search({
                    lang: lang,
                    untranslatedLang: data.data.message_lang
                  });
                }

                $uibModalInstance.close();

              },
              function () {
                $uibModalInstance.close();
              }
            );
          }
        }
      );
    }

    function save(andClose) {
      doSave(andClose);

    }

    function onChangeValue(key, mainKey, value) {
      if(key == 'treeParent' && value == vm.object.id){
        return;
      }
      //console.log(key, mainKey, value);
      if (!mainKey) {
        objectDataToSave[key] = value;
      } else {
        if (!objectDataToSave[mainKey]) {
          objectDataToSave[mainKey] = {};
        }
        objectDataToSave[mainKey][key] = value;
      }

      parseViewState();

    }


  }

})();
