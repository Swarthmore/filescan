define([], function () {
    window.requirejs.config({
        paths: {
            "datatables": "/blocks/filescan/lib/datatables.min",
        },
        shim: {
            'datatables': {exports: 'DataTables'},
        }
    });
});