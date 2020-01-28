(function() {
    'use strict';

    angular
        .module('app')
        .directive('pimI18npermissions', pimI18npermissions);


    function pimI18npermissions(localStorageService){
        return {
            restrict: 'E',
            scope: {
                key: '=', config: '=', value: '=', isValid: '=', isSubmit: '=', onChangeCallback: '&'
            },
            templateUrl: function(){
                return 'lib/contentfly-ui/assets/types/i18npermissions/i18npermissions.html?v=' + APP_VERSION
            },
            link: function(scope, element, attrs){

              //Variables
              var frontend = localStorageService.get('frontend');
              var mapping  = {
                0 : 'readable',
                1 : 'translatable'
              };

              //Properties
              scope.langs = {
                main : null,
                other: null
              };


              //Functions
              scope.changePermission = changePermission;

              //Init
              init();


              ///////////////////////


              function changePermission(isMainLang, lang){
                var key =  isMainLang ? 'main' : 'other';

                switch (scope.langs[key][lang]){
                  case 0:
                    scope.langs[key][lang] = 2;
                    break;
                  case 1:
                    scope.langs[key][lang] = 0;
                    break;
                  default:
                    scope.langs[key][lang] = 1;
                    break;
                }

                scope.onChangeCallback({key: scope.key, value: mapToValue()});
              }

              function getReverseMapping(value){
                for (var key in mapping) {
                  if(mapping[key] == value){
                    return key;
                  }
                }

                return 2;
              }

              function init(){

                if(frontend.languages){
                  scope.langs.main = {};
                  scope.langs.main[frontend.languages[0]] = 2;

                  if(frontend.languages.length > 1){
                    scope.langs.other = {};
                    for(var i = 1; i < frontend.languages.length; i++){
                      var lang = frontend.languages[i];
                      scope.langs.other[lang] = 2;
                    }
                  }
                }

                scope.$watch('value',function(data){
                  if(data){
                    for (var lang in data) {
                      if(lang == frontend.languages[0]){
                        if(!scope.langs.main){
                          scope.langs.main = {};
                        }
                        scope.langs.main[lang] = getReverseMapping(data[lang]);
                      }else{
                        if(!scope.langs.other){
                          scope.langs.other = {};
                        }
                        scope.langs.other[lang] = getReverseMapping(data[lang]);
                      }
                    }
                  }
                },true);
              }

              function mapToValue(){
                var value = {};
                if(scope.langs.main[frontend.languages[0]] != 2 && mapping[scope.langs.main[frontend.languages[0]]]){
                  value[frontend.languages[0]] = mapping[scope.langs.main[frontend.languages[0]]];
                }

                for (var lang in scope.langs.other) {
                  if(scope.langs.other[lang] != 2 && mapping[scope.langs.other[lang]]){
                    value[lang] = mapping[scope.langs.other[lang]];
                  }
                }

                return value;
              }

            }
        }
    }

})();
