app.directive('pimForm', function($filter) {
    return {
        restrict: 'AE',
        scope: { object: '=', schema: '=', tabkey: '=', form: '=', submitted: '='},
        templateUrl: function(elem, attr){
            return 'views/directives/form.html';
        }
    }
});