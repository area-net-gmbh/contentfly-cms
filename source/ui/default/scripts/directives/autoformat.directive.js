(function() {
    'use strict';

    angular
        .module('app')
        .directive('pimAutoformat', pimAutoformat);

    function pimAutoformat($filter, localStorageService){
        return {
            restrict: 'AEC',
            scope: { object: '=', schema: '=', long: '=' },
            link: function(scope, element, attrs){
                
                var property = attrs.property;
                var long     = attrs.long ? attrs.long : false;
                var type     = scope.schema.properties[property].type;

                scope.$watch('object', function() {
                    
                    if(scope.object == null){
                        return;
                    }

                    switch(type) {
                        case 'datetime':
                            //var value = $filter('date')(scope.object[property], 'dd.MM.yyyy');
                            var value = long ? scope.object[property].LOCAL_TIME : scope.object[property].LOCAL;
                            element.text(value);
                            break;
                        case 'boolean':
                            element.text(scope.object[property] ? 'Ja' : 'Nein');
                            break;
                        case 'join':
                            var fullEntity    = scope.schema.properties[property].accept.split('\\');
                            var entity        = fullEntity[(fullEntity.length - 1)];
                            var joinSchema    = localStorageService.get('schema')[entity]
                            var firstProperty = joinSchema.list[Object.keys(joinSchema.list)[0]];
                            
                            element.text(scope.object[property][firstProperty]);
                            break;
                        default:
                            element.text(scope.object[property]);
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
                })




            }
        }
    }
})();