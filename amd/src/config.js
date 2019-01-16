define([], function () {
    window.requirejs.config({
        paths: {
            "datatables": "http://moodle.aws-dev.swarthmore.edu/blocks/filescan/lib/datatables.min",
        },
        shim: {
            'datatables': {exports: 'DataTables'},
        }
    });
});