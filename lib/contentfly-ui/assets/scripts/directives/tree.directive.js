(function() {
  'use strict';

  angular
    .module('app')
    .directive('pimTree', pimTree);


  function pimTree(localStorageService, $rootScope){
    return {
      restrict: 'E',
      scope: {
        items: '=', entity: '=', value: '=', key: '=',  selectMode: '=', sword: "=", onSelectCallback: '&', showCancelSearch: "="
      },
      templateUrl: function(){
        return 'lib/contentfly-ui/assets/views/directives/tree.html?v=' + APP_VERSION
      },
      link: function(scope, element, attrs){

        var treeState       = {};

        //Properties
        scope.clicked     = false;
        scope.cancelClick = false
        scope.nodeOpened  = {};
        scope.schema      = null;

        //Functions
        scope.clearSearch = clearSearch;
        scope.label     = label;
        scope.sItems    = [];
        scope.isOpened  = isOpened;
        scope.isSelected= isSelected;
        scope.select    = select;
        scope.selectAll = selectAll;
        scope.selectRoot= selectRoot;
        scope.toggle    = toggle;

        init();

        /////////////////////////////////////

        function clearSearch($event){
          scope.sword = '';
          $event.stopPropagation();
        }

        function init(){

          scope.entity = $rootScope.getShortEntityName(scope.entity);
          scope.schema = localStorageService.get('schema')[scope.entity];

          treeState = localStorageService.get('treeState');
          treeState = treeState ? treeState : {};

          scope.nodeOpened = treeState[scope.entity] ? treeState[scope.entity] : {};

          scope.$watch('sword',function(data){
            filter();
          },true)

        }

        function find(items, parentLabel){

          if(!items.length){
            return;
          }

          for(var i in items){
            var item  = items[i];
            var itemLabel = label(item);

            if(itemLabel.toLowerCase().includes(scope.sword.toLowerCase())){

              var labelProperty = scope.schema.settings.labelProperty ? scope.schema.settings.labelProperty : scope.schema.list[0];
              var sItem = {};
              sItem['id'] = item.id;
              sItem[labelProperty] = parentLabel + itemLabel;
              scope.sItems.push(sItem);

            }

            find(item.childs, parentLabel + itemLabel + ' -> ');
          }

        }

        function filter(){
          if(!scope.sword){
            return;
          }

          scope.sItems = [];

          find(scope.items, '')

        }

        function labelProperty(){
          return scope.schema.settings.labelProperty ? scope.schema.settings.labelProperty : scope.schema.list[0];
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
          return Array.isArray(scope.value) ? scope.value.includes(item.id) :item.id == scope.value;
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
