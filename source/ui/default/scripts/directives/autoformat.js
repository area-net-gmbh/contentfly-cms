app.directive('pimAutoformat', function($filter) {
    return {
        restrict: 'AEC',
        scope: { object: '=', schema: '=', long: '=' },
        link: function(scope, element, attrs){
            //var object = JSON.parse(attrs.object);

            var property = attrs.property;
            var long     = attrs.long ? attrs.long : false;
            var type     = scope.schema.properties[property].type;

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
                    element.text(scope.object[property].title);
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
        }
    }
});