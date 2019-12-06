(function() {
    'use strict';

    angular
        .module('app')
        .directive('pimAutoformat', pimAutoformat);

    function pimAutoformat($filter, localStorageService, $rootScope){
        return {
            restrict: 'AEC',
            scope: { object: '=', schema: '=', long: '=' },
            link: function(scope, element, attrs){


                scope.$watch('object', function() {

                    var property = attrs.property;
                    var long     = attrs.long ? attrs.long : false;
                    var type     = scope.schema.properties[property] ? scope.schema.properties[property].type : null;

                    if(scope.object == null || type == null){
                        return;
                    }

                    switch(type) {
                        case 'datetime':
                            //var value = $filter('date')(scope.object[property], 'dd.MM.yyyy');
                            if(!scope.object[property] || !scope.object[property]['ISO8601']){
                               element.text('');
                                return;
                            }
                            var momentJS = moment(scope.object[property].ISO8601);
                            element.text(momentJS.format(scope.schema.properties[property].format));
                            break;
                          case 'file':
                            element.text(scope.object[property]['name']);
                            break;
                        case 'boolean':
                            element.text(scope.object[property] ? 'Ja' : 'Nein');
                            break;
                          case 'virtualjoin':
                              var ids = [];
                              if(scope.object[property]){
                                for(var i = 0; i < scope.object[property].length; i++){
                                  ids.push(scope.object[property][i]['id']);
                                }
                              }

                              element.text(ids.join(', '));
                            break;
                        case 'join':
                        case 'radio':
                            var entity  = $rootScope.getShortEntityName(scope.schema.properties[property].accept);

                            var joinSchema    = localStorageService.get('schema')[entity];

                            if(scope.object[property]){

                              if(joinSchema.settings.labelProperty){
                                element.text(scope.object[property][joinSchema.settings.labelProperty]);
                              }else{
                                var firstProperty = joinSchema.list[Object.keys(joinSchema.list)[0]];
                                element.text(scope.object[property][firstProperty]);
                              }

                            } else {
                              element.text('');
                            }
                            break;
                        case 'select':
                            var value = scope.object[property];
                            var options = scope.schema.properties[property].options;
                            //console.log(options);
                            for(var i = 0; i < options.length; i++){
                                if(options[i].id == value){
                                    element.text(options[i].name);
                                    return;
                                }
                            }
                            element.text(value);
                            break;
                        default:
                            var listShorten = scope.schema.properties[property].listShorten;
                            if(property == 'id'){
                                listShorten = 5;
                            }

                            var content = strip_tags(scope.object[property]);
                            if(listShorten){
                                if(content && content.length > listShorten){
                                    element.text(content.substr(0, listShorten) + '...');
                                }else{
                                    element.text(content);
                                }

                            }else{
                                element.text(content);
                            }

                            break;
                    }

                    switch(property) {
                        case 'user':
                            var alias = scope.object[property] ? scope.object[property].alias : 'admin';
                            element.text(alias);
                            break;
                        default:
                            break;
                    }
                }, true);

                function strip_tags(input, allowed){
                    if (!(typeof input === 'string' || input instanceof String)){
                        return input;
                    }

                    allowed = (((allowed || '') + '').toLowerCase().match(/<[a-z][a-z0-9]*>/g) || []).join('')

                    var tags = /<\/?([a-z][a-z0-9]*)\b[^>]*>/gi
                    var commentsAndPhpTags = /<!--[\s\S]*?-->|<\?(?:php)?[\s\S]*?\?>/gi

                    return input.replace(commentsAndPhpTags, '').replace(tags, function ($0, $1) {
                        return allowed.indexOf('<' + $1.toLowerCase() + '>') > -1 ? $0 : ''
                    })
                }




            }
        }
    }
})();