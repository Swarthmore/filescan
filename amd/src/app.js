define([
  "core/ajax",
  "jquery",
  "block_filescan/jquery.dataTables",
  "block_filescan/buttons.html5",
  "block_filescan/dataTables.buttons",
  "block_filescan/dataTables.select",
], function (Ajax, $) {

"use strict";

function _instanceof(left, right) { if (right != null && typeof Symbol !== "undefined" && right[Symbol.hasInstance]) { return !!right[Symbol.hasInstance](left); } else { return left instanceof right; } }

function _classCallCheck(instance, Constructor) { if (!_instanceof(instance, Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

var ProgressBar =
/*#__PURE__*/
function () {
  function ProgressBar(_ref) {
    var id = _ref.id,
        value = _ref.value,
        type = _ref.type;

    _classCallCheck(this, ProgressBar);

    this._value = value;
    this._type = type;
    this._el = $("<div>").addClass("overflow-hidden progress-bar bg-" + this._type).attr({
      id: id
    }).css({
      width: this._value
    });
  }

  _createClass(ProgressBar, [{
    key: "update",
    value: function update(_ref2) {
      var value = _ref2.value,
          type = _ref2.type;
      this._value = value;
      this._type = type;

      this._el.removeClass().addClass("overflow-hidden progress-bar bg-" + this._type).css({
        width: value
      });
    }
  }, {
    key: "value",
    get: function get() {
      return this._value;
    }
  }, {
    key: "el",
    get: function get() {
      return this._el;
    }
  }]);

  return ProgressBar;
}();
  // this returns control of the $ variable to Moodle's jQuery
  $.noConflict(true);

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
          
          $("#btn-export-csv").prop({
            disabled: true
          });

          var promise = exportData()[0];
          promise
            .done(function(res){
              $("#btn-export-csv").prop({ 
                "disabled": false
              });
              const count = res.recordsTotal;
              getAllRecords(count);
            })
            .fail(function(err) {
              $("#btn-export-csv").prop({ 
                "disabled": false
              });
              handleError(err);
            })
        });

        /**
         * @desc This will return the number of filescan records
         * @note This is kind of hacky and there might be a better way to do this
         */
        function exportData() {
          return Ajax.call([
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
    },

    progress: function(text, title, language, outline, total ) {

      var $statsText = $("#stats-text");
      var $statsTitle = $("#stats-title");
      var $statsLanguage = $("#stats-language");
      var $statsOutline = $("#stats-outline");
       
      function makePctStr(val, total) {
        return Math.round((val / total) * 100) + "%";
      }

       // Initialize everything to zero
       // We do this because we then want to change the css property to
       // the actual value 
       var $textProgress = new ProgressBar({
        value: makePctStr(0, 100),
        type: "primary"
       });

       var $titleProgress = new ProgressBar({
        value: makePctStr(0, 100),
        type: "primary"
       });

       var $languageProgress = new ProgressBar({
        value: makePctStr(0, 100),
        type: "primary"
       });

       var $outlineProgress = new ProgressBar({
        value: makePctStr(0, 100),
        type: "primary"
       });

       $statsText.append($textProgress.el);
       $statsTitle.append($titleProgress.el);
       $statsLanguage.append($languageProgress.el);
       $statsOutline.append($outlineProgress.el);

       // Once everything is loaded, update the progress 
       // bar to trigger an animation
       setTimeout(function(){

        $textProgress.update({
          value: makePctStr(text, total),
          type: "primary"
        });

        $textProgress.el.text(text + " / " + total + " pdfs have text")

        $titleProgress.update({
          value: makePctStr(title, total),
          type: "primary"
        });

        $titleProgress.el.text(title + " / " + total + " pdfs have a title")

        $languageProgress.update({
          value: makePctStr(language, total),
          type: "primary"
        });

        $languageProgress.el.text(language + " / " + total +  " pdfs have a language")

        $outlineProgress.update({
          value: makePctStr(outline, total),
          type: "primary"
        });

        $outlineProgress.el.text(outline + " / " + total + " pdfs have an outline")

       }, 500);

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
