define([
  "core/ajax",
  "jquery",
  "block_filescan/jquery.dataTables",
  "block_filescan/buttons.html5",
  "block_filescan/dataTables.buttons",
  "block_filescan/dataTables.select",
], function (Ajax, $) {

  // this returns control of the $ variable to Moodle's jQuery
  $.noConflict(true);

  // keep track of the app state here
  const state = {
    isLoading: false
  }

  return {

    init: function() {

      $(document).ready(function() {

        const dt = $("#view").DataTable({
          processing: true,
          serverSide: true,
          lengthMenu: [ [50, 100, 200, -1], [50, 100, 200, "All"] ],
          ajax: function(data, cb, settings) {
            Ajax.call([
              {
                methodname: "block_filescan_request_files",
                args: data, 
                done: function(res){ cb(res); },
                fail: function(err){ handleError("Could not get data from the Moodle API: ", err) }
              }
            ]);
          },
          select: true,
          dom: "Blfrtip", 
          buttons: ["copy"],
          columnDefs: [{  targets: "id", visible: false  }],
          columns: [
            {
              data: "status",
              className: "text-center",
              render: function(data) { return getStatusIcon(data); }
            },
            {
              data: "hastext",
              className: "text-center",
              render: function(data) { return getIcon(data); }
            },
            {
              data: "hastitle",
              className: "text-center",
              render: function(data) { return getIcon(data); }
            },
            {
              data: "hasoutline",
              className: "text-center",
              render: function(data) { return getIcon(data); }
            },
            {
              data: "haslanguage",
              className: "text-center",
              render: function(data) { return getIcon(data); }
            },
            {
              data: "timechecked",
              className: "text-center",
              render: function(data) { return ConvertDateFromDiv(Date.parse(data)); }
            },
            {
              data: "courseinfo",
              render: function(data, type, row, meta) {
                var url = "/course/view.php?id=";

                if (data) {
                  var d = JSON.parse(data);
                  var courses = [];

                  d.forEach(function(course) {

                    var courseUrl = (M.cfg.wwwroot + url + course.courseid) || 0;
                    var enrolled = course.student_enrollment || 0;

                    var teachers = parseTeachers(course);

                    if (course) {
                      
                      var courseStr = $("<p></p>").text(
                        makeUrl(courseUrl, course.shortname)
                      );
     
                      var cStr = '<p>' + 
                        makeUrl(courseUrl, course.shortname) + 
                        '</p>';
                      var tStr = '<p>' + 
                        '<i class="fa fa-chalkboard-teacher mr-1"></i>' + 
                        teachers + 
                        '</p>'
                      var fStr = '<p class="mb-1">' + 
                        makeUrl(M.cfg.wwwroot + 
                          '/mod/resource/view.php?id=' + 
                          course.instance_id, course.filename
                        ) + '</p>';
                      courses.push(cStr + tStr + fStr);
                    } else {
                      courses.push(
                        "No course information found. This may be an invalid courseinfo \
                        entry within the block_filescan_files table."
                      );
                    }
                  });
                  return courses.join("<br>");
                } else {
                  return 'No response returned from the DataTables service. \
                  Are you sure the course exists?<br><strong>Ref id: ' + 
                    row["id"] + 
                    '</strong>';
                }
              }
            }
          ]
        });
        
        $('#btn-reload').on("click", function () {
          dt.ajax.reload();
        });

        $("#btn-export-csv").on("click", function () {
          exportData();
        });

        /**
         * @desc This will return the number of filescan records
         * @note This is kind of hacky and there might be a better way to do this
         */
        function exportData() {
          Ajax.call([
            {
              methodname: "block_filescan_request_files",
              args: {
                "draw": 1,
                "columns": [
                  {
                    "data": "status",
                    "name": "",
                    "searchable": true,
                    "orderable": true,
                    "search": {
                      "value": "",
                      "regex": false
                    }
                  },
                  {
                    "data": "hastext",
                    "name": "",
                    "searchable": true,
                    "orderable": true,
                    "search": {
                      "value": "",
                      "regex": false
                    }
                  },
                  {
                    "data": "hastitle",
                    "name": "",
                    "searchable": true,
                    "orderable": true,
                    "search": {
                      "value": "",
                      "regex": false
                    }
                  },
                  {
                    "data": "hasoutline",
                    "name": "",
                    "searchable": true,
                    "orderable": true,
                    "search": {
                      "value": "",
                      "regex": false
                    }
                  },
                  {
                    "data": "haslanguage",
                    "name": "",
                    "searchable": true,
                    "orderable": true,
                    "search": {
                      "value": "",
                      "regex": false
                    }
                  },
                  {
                    "data": "timechecked",
                    "name": "",
                    "searchable": true,
                    "orderable": true,
                    "search": {
                      "value": "",
                      "regex": false
                    }
                  },
                  {
                    "data": "courseinfo",
                    "name": "",
                    "searchable": true,
                    "orderable": true,
                    "search": {
                      "value": "",
                      "regex": false
                    }
                  }
                ],
                "order": [
                  {
                    "column": 0,
                    "dir": "asc"
                  }
                ],
                "start": 0,
                "length": 1,
                "search": {
                  "value": "",
                  "regex": false
                }
              },
              done: function(res) {
                const count = res.recordsTotal;
                getAllRecords(count);
              },
              fail: function(err) {
                throw new Error("Could not get data from the Moodle API: ", err);
              }
            }
          ]);
        }

        /**
         * @desc This will return all records in the filescan table
         * and should be used for exporting data, unless a native way in datatables
         * is found (right now because we are usign server-side processing, it's not
         * easy to do this)
         * @param  count 
         */
        function getAllRecords(count) {
          Ajax.call([
            {
              methodname: "block_filescan_request_files",
              args: {
                "draw": 1,
                "columns": [
                  {
                    "data": "status",
                    "name": "",
                    "searchable": true,
                    "orderable": true,
                    "search": {
                      "value": "",
                      "regex": false
                    }
                  },
                  {
                    "data": "hastext",
                    "name": "",
                    "searchable": true,
                    "orderable": true,
                    "search": {
                      "value": "",
                      "regex": false
                    }
                  },
                  {
                    "data": "hastitle",
                    "name": "",
                    "searchable": true,
                    "orderable": true,
                    "search": {
                      "value": "",
                      "regex": false
                    }
                  },
                  {
                    "data": "hasoutline",
                    "name": "",
                    "searchable": true,
                    "orderable": true,
                    "search": {
                      "value": "",
                      "regex": false
                    }
                  },
                  {
                    "data": "haslanguage",
                    "name": "",
                    "searchable": true,
                    "orderable": true,
                    "search": {
                      "value": "",
                      "regex": false
                    }
                  },
                  {
                    "data": "timechecked",
                    "name": "",
                    "searchable": true,
                    "orderable": true,
                    "search": {
                      "value": "",
                      "regex": false
                    }
                  },
                  {
                    "data": "courseinfo",
                    "name": "",
                    "searchable": true,
                    "orderable": true,
                    "search": {
                      "value": "",
                      "regex": false
                    }
                  }
                ],
                "order": [
                  {
                    "column": 0,
                    "dir": "asc"
                  }
                ],
                "start": 0,
                "length": count,
                "search": {
                  "value": "",
                  "regex": false
                }
              },
              done: function(res) {
                // csv checks out as ok
                const csv = parseDataToCsv(res.data);
                save(Date.now().toString() + "_filescan" + ".csv", csv);
              },
              fail: function() {
                handleError("Could not get data from the Moodle API!");
              }
            }
          ]);
        }

        /**
         * @desc Parses data received from the filescan table to a CSV
         * @param {*} data 
         */
        function parseDataToCsv(arr) {
          const array = [Object.keys(arr[0])].concat(arr)
        
          return array.map(function(it) {
            return Object.values(it).toString()
          }).join('\n')
        }

        /**
         * @desc Creates a file download
         * @param String filename 
         * @param {*} data 
         */
        function save(filename, data) {
          var blob = new Blob([data], {type: 'text/csv'});
          if (window.navigator.msSaveOrOpenBlob) {
            window.navigator.msSaveBlob(blob, filename);
          }
          else {
            var elem = window.document.createElement('a');
            elem.href = 'data:text/csv;charset=utf-8,' + encodeURI(data);
            elem.download = filename;        
            document.body.appendChild(elem);
            elem.click();        
            document.body.removeChild(elem);
          }
        }

        function handleError(str) {
          throw new Error(str);
        }
      });
    }
  };
});

/**
 * @desc It's easier to write a function to create links than to deal with
 * poor JS interpolation (Because moodle doesn't support new js at this time -_-)
 * @param String href 
 * @param String text 
 */
function makeUrl(href, text) {
  var urlRegex = /^(?:http(s)?:\/\/)?[\w.-]+(?:\.[\w\.-]+)+[\w\-\._~:/?#[\]@!\$&'\(\)\*\+,;=.]+$/;
  if (!urlRegex.test(href)) {
    throw new Error("Invalid URL passed to makeUrl function: ", href);
  }
  return '<a href="' + href + '">' + text + '</a>'
}

/**
 * Converts a unix timestamp to something that is human readable
 *
 * @param divTimeStr
 * @returns {string}
 * @constructor
 */
function ConvertDateFromDiv(divTimeStr) {
  var tmstr = divTimeStr.toString().split(" ");
  var dt = tmstr[0].split("/");
  var str = dt[2] + "/" + dt[1] + "/" + dt[0] + " " + tmstr[1];
  var time = new Date(str);

  if (time == "Invalid Date") {
    time = new Date(divTimeStr);
  }

  return time.toLocaleDateString();
}

/**
 * Returns a Bootstrap icon statement.
 *
 * @param bool
 * @returns {string}
 */
function getIcon(bool) {
  if (bool) {
    return '<i class="fa fa-check text-success fa-fw"></i>';
  } else {
    return '<i class="fa fa-times text-danger fa-fw"></i>';
  }
}

/**
 * Returns a Bootstrap icon statement.
 *
 * @param status
 * @returns {string}
 */
function getStatusIcon(status) {
  if (status === "check") {
    return '<i class="fa fa-exclamation text-warning fa-fw"></i>';
  } else if (status === "fail") {
    return '<i class="fa fa-exclamation-triangle text-warning fa-fw"></i>';
  } else if (status === "error") {
    return '<i class="fa fa-times text-danger fa-fw"></i>';
  } else if (status === "pass") {
    return '<i class="fa fa-check text-success fa-fw"></i>';
  } else {
    return "error";
  }
}

function _slicedToArray(arr, i) {
  return (
    _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _nonIterableRest()
  );
}

function _nonIterableRest() {
  throw new TypeError("Invalid attempt to destructure non-iterable instance");
}

function _iterableToArrayLimit(arr, i) {
  if (
    !(
      Symbol.iterator in Object(arr) ||
      Object.prototype.toString.call(arr) === "[object Arguments]"
    )
  ) {
    return;
  }
  var _arr = [];
  var _n = true;
  var _d = false;
  var _e = undefined;
  try {
    for (
      var _i = arr[Symbol.iterator](), _s;
      !(_n = (_s = _i.next()).done);
      _n = true
    ) {
      _arr.push(_s.value);
      if (i && _arr.length === i) break;
    }
  } catch (err) {
    _d = true;
    _e = err;
  } finally {
    try {
      if (!_n && _i["return"] != null) _i["return"]();
    } finally {
      if (_d) throw _e;
    }
  }
  return _arr;
}

function _arrayWithHoles(arr) {
  if (Array.isArray(arr)) return arr;
}

function parseTeachers(course) {
  var teachers = [];
  Object.entries(course).forEach(function(_ref) {
    var _ref2 = _slicedToArray(_ref, 2),
      key = _ref2[0],
      val = _ref2[1];

    if (key === "teachers") {
      Object.entries(val).forEach(function(_ref3) {
        var _ref4 = _slicedToArray(_ref3, 2),
          teacherId = _ref4[0],
          teacherInfo = _ref4[1];

        teachers += teacherInfo.firstname + " " + teacherInfo.lastname + "<br>";
      });
    }
  });
  return teachers;
}
