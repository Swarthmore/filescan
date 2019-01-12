define([], function(){
  window.requirejs.config({
    paths: {
      // Paths to required js libraries
      "DataTables": "../lib/datatables.min.js"
    },
    shim: {
      // The names that will be used to refer to lib
      DataTables: {
        deps: ["jquery"],
        exports: "$.fn.DataTable"
      }
    }
  })
});