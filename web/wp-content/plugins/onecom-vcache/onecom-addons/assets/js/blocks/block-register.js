(()=>{"use strict";var e=wp.i18n.__,t=wp.compose.compose,c=wp.data,o=c.withSelect,n=c.withDispatch,r=wp.editPost.PluginDocumentSettingPanel,i=wp.components,a=i.ToggleControl;i.PanelRow;const l=t([o((function(e){return{postMeta:e("core/editor").getEditedPostAttribute("meta"),postType:e("core/editor").getCurrentPostType()}})),n((function(e){return{setPostMeta:function(t){e("core/editor").editPost({meta:t})}}}))])((function(t){var c=t.postType,o=t.postMeta,n=t.setPostMeta;return"post"!==c&&"page"!==c?null:React.createElement(r,{title:e("Performance Cache","vcaching"),icon:"performance",initialOpen:"true"},React.createElement(a,{label:blockObject.label,help:o._oct_exclude_from_cache?sprintf(blockObject.excludeText,c):sprintf(blockObject.includeText,c),onChange:function(e){e||(e=0),n({_oct_exclude_from_cache:e})},checked:o._oct_exclude_from_cache}))}));(0,wp.plugins.registerPlugin)("onecom-exclude-cache-plugin",{render:function(){return React.createElement(l,null)}})})();