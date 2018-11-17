

(function() {
    'use strict';

    angular
        .module('app')
        .directive('pimRte', pimRte);


    function pimRte(localStorageService, $sce){
        return {
            restrict: 'E',
            scope: {
                key: '=', config: '=', value: '=', isValid: '=', isSubmit: '=', onChangeCallback: '&'
            },
            templateUrl: function(){
                return '/ui/default/types/rte/rte.html?v=' + APP_VERSION
            },
            link: function(scope, element, attrs){
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
                    statusbar: false,
                    menubar: false,
                    language: 'de',
                    paste_as_text: true,
                    autoresize_bottom_margin: 20,
                    plugins: "lists, link,anchor, code,autoresize,stickytoolbar2, paste",
                    toolbar1: scope.config.rteToolbar
                  };

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
