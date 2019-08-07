

(function() {
    'use strict';

    angular
        .module('app')
        .directive('pimRte', pimRte);


    function pimRte(localStorageService, $sce, $uibModal, $state, $rootScope){
        return {
            restrict: 'E',
            scope: {
                key: '=', config: '=', value: '=', isValid: '=', isSubmit: '=', onChangeCallback: '&'
            },
            templateUrl: function(){
                return '/ui/default/types/rte/rte.html?v=' + APP_VERSION
            },
            link: function(scope, element, attrs, ){
                scope.disabled = !parseInt(attrs.writable) || scope.config.readonly;

                //scope.trustedValue = $sce.trustAsHtml(scope.value);
                if(!scope.disabled){
                  scope.tinymceOptions = {
                    setup:function(ed) {
                      ed.on('change', function(e) {
                        scope.onChangeCallback({key: scope.key, value: ed.getContent()});
                      });
                    },
                    skin: 'lightgray',
                    theme : 'modern',
                    statusbar: true,
                    menubar: false,
                    language: 'de',
                    paste_as_text: true,
                    autoresize_bottom_margin: 20,
                    plugins: "lists, link,anchor, code,autoresize,stickytoolbar2, paste image",
                    block_formats: 'Absatz=p;Überschrift 1=h1;Überschrift 2=h2;Überschrift 3=h3;Überschrift 4=h4;Überschrift 5=h5;Überschrift 6=h6;Zitat=blockquote;Code=pre',
                    toolbar1: scope.config.rteToolbar,
                    file_picker_callback: function(callback, value, meta) {

                      if(meta.filetype == 'file'){
                        //TODO: doSelect wird nicht mit übergeben

                        $state.transitionTo('list.select', {entity: 'Kategorie', doSelect: true}, {
                          location: false,
                          inherit: true,
                          relative: $state.$current,
                          notify: false
                        });

                        $rootScope.$on('OBJECT_SELECTED', function(evt, data) {
                          callback('intern://' + data.entity + ':' + data.id);
                        });
                      }


                      if (meta.filetype == 'image') {

                        var modalInstance = $uibModal.open({
                          templateUrl: '/ui/default/views/files.html',
                          controller: 'FilesCtrl as vm',
                          windowClass: 'zindex-top',
                          resolve: {
                            modaltitle: function () {
                              return 'Datei auswählen';
                            },
                            property: function () {
                              return 'test';
                            },
                            pimEntity: function () {
                              return true;
                            },
                            '$extend': function(){ return null;}
                          },
                          size: 'xl'
                        });


                        modalInstance.result.then(function (fileData) {
                          if(!fileData.type.includes('image')){

                            return;
                          }
                          callback('/file/get/' + fileData.id + '/' + fileData.name, {alt: fileData.name});
                        });

                      }

                    }
                  };

                  if(scope.config.extend && Array.isArray(scope.config.extend)){
                    for(var i in scope.config.extend){
                      scope.config.extend[i] = scope.config.extend[i].replace(new RegExp('\'', 'g'), '"');
                      try {
                        var extend = JSON.parse(scope.config.extend[i]);
                        angular.extend(scope.tinymceOptions, extend);
                      }catch(e){
                        console.log("[JSON-ERROR rte::extend] " + scope.config.extend[i]);
                      }

                    }
                  }

                  if(scope.config.typeExtend){
                    scope.config.typeExtend = scope.config.typeExtend.replace(new RegExp('\'', 'g'), '"');
                    try {
                      var extend = JSON.parse(scope.config.typeExtend);
                      angular.extend(scope.tinymceOptions, extend);
                    }catch(e){
                      console.log("[JSON-ERROR rte::typeExtend] " + scope.config.typeExtend);
                    }

                  }
                }



                if(scope.value === undefined && scope.config.default != null){
                    scope.value = (scope.config.default);
                }

            }
        }
    }


  tinymce.PluginManager.add('stickytoolbar2', function(editor, url) {
    editor.on('init', function() {
      setSticky();
    });


    function setSticky() {
      var container = editor.editorContainer;
      var toolbars = $(container).find('.mce-toolbar-grp');

      toolbars.css({
        top: 0,
        bottom: 'auto',
        position: 'sticky'
      });


    }

  });



})();
