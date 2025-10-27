/******/ (() => { // webpackBootstrap
/******/ 	"use strict";

;// external ["wp","plugins"]
const external_wp_plugins_namespaceObject = window["wp"]["plugins"];
;// external ["wp","i18n"]
const external_wp_i18n_namespaceObject = window["wp"]["i18n"];
;// external ["wp","editSite"]
const external_wp_editSite_namespaceObject = window["wp"]["editSite"];
;// ./assets/js/src/editors/site.js
/**
 * Site Editor sidebar bootstrap
 */




var SIDEBAR_NAME = 'ewt-site-sidebar';
var Sidebar = function Sidebar() {
  return /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement(external_wp_editSite_namespaceObject.PluginSidebarMoreMenuItem, {
    target: SIDEBAR_NAME
  }, (0,external_wp_i18n_namespaceObject.__)('Languages', 'easy-wp-translator')), /*#__PURE__*/React.createElement(external_wp_editSite_namespaceObject.PluginSidebar, {
    name: SIDEBAR_NAME,
    title: (0,external_wp_i18n_namespaceObject.__)('Languages', 'easy-wp-translator')
  }, /*#__PURE__*/React.createElement("div", {
    className: "ewt-sidebar-section"
  }, /*#__PURE__*/React.createElement("p", null, (0,external_wp_i18n_namespaceObject.__)('EasyWPTranslator sidebar (Site Editor)', 'easy-wp-translator')))));
};
(0,external_wp_plugins_namespaceObject.registerPlugin)(SIDEBAR_NAME, {
  render: Sidebar
});
/******/ })()
;