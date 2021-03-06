/**
 * @const {lx.Plugin} Plugin
 * @const {lx.Snippet} Snippet
 */

Plugin.classes = {};
#lx:require pluginClasses/;
Plugin.core = new Plugin.classes.Core(Plugin);
