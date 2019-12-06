(function() {
  'use strict';

  angular
    .module('app')
    .directive('pimTree', pimTree);


  function pimTree(localStorageService, $rootScope){
    return {
      restrict: 'E',
      scope: {
        items: '=', entity: '=', value: '=', key: '=', onSelectCallback: '&'
      },
      templateUrl: function(){
        return '/ui/default/views/directives/tree.html?v=' + APP_VERSION
      },
      link: function(scope, element, attrs){

        var treeState    = {};

        //Properties
        scope.clicked     = false;
        scope.cancelClick = false
        scope.nodeOpened  = {};
        scope.schema      = null;

        //Functions
        scope.label     = label;
        scope.isOpened  = isOpened;
        scope.isSelected= isSelected;
        scope.select    = select;
        scope.selectAll = selectAll;
        scope.selectRoot= selectRoot;
        scope.toggle    = toggle;

        init();

        /////////////////////////////////////

        function init(){
          scope.entity = $rootScope.getShortEntityName(scope.entity);
          scope.schema = localStorageService.get('schema')[scope.entity];


          treeState = localStorageService.get('treeState');
          treeState = treeState ? treeState : {};

          scope.nodeOpened = treeState[scope.entity] ? treeState[scope.entity] : {};
        }

        function label(item){

          if(scope.schema.settings.labelProperty){
            return item[scope.schema.settings.labelProperty];
          }

          return item[scope.schema.list[0]];
        }

        function isOpened(id){
          return scope.nodeOpened[id] ? true : false;
        }

        function isSelected(item){
          return item.id == scope.value;
        }


        function select(item){
          var data = {key: scope.key, item: item};
          scope.onSelectCallback(data);
        }

        function selectAll(){
          var data = {key: scope.key, item: {id: null}};
          scope.onSelectCallback(data);
        }

        function selectRoot(){
          var data = {key: scope.key, item: {id: -1}};
          scope.onSelectCallback(data);
        }


        function toggle($event, id){

          scope.nodeOpened[id] = scope.nodeOpened[id] ? false : true;

          treeState[scope.entity] = scope.nodeOpened;
          localStorageService.set('treeState', treeState);

          $event.stopPropagation();
        }
      }
    }
  }

})();
