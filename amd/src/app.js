define([
  "core/config",
  "core/ajax",
  "jquery",
  "block_filescan/jquery.dataTables",
  "block_filescan/buttons.html5",
  "block_filescan/dataTables.buttons",
  "block_filescan/dataTables.select",
], function (Config, Ajax, $, Datatables) {

  return {
    init: function() {
      $(document).ready(function() {

        $.noConflict(true); // this returns control of the $ variable to Moodle's jQuery

        $("#view").DataTable({
          processing: true,
          serverSide: true,
          ajax: function(data, cb, settings) {
            Ajax.call([
              {
                methodname: "block_filescan_request_files",
                args: data,
                done: function(res) {
                  cb(res);
                },
                fail: function() {
                  console.error("Could not get data!");
                }
              }
            ]);
          },
          select: true,
          dom: "Bfrtip", 
          buttons: ["copy", "csv"],
          columnDefs: [{ targets: "id", visible: false }],
          columns: [
            {
              data: "status",
              className: "text-center",
              render: function(data) {
                return getStatusIcon(data);
              }
            },
            {
              data: "hastext",
              className: "text-center",
              render: function(data) {
                return getIcon(data);
              }
            },
            {
              data: "hastitle",
              className: "text-center",
              render: function(data) {
                return getIcon(data);
              }
            },
            {
              data: "hasoutline",
              className: "text-center",
              render: function(data) {
                return getIcon(data);
              }
            },
            {
              data: "haslanguage",
              className: "text-center",
              render: function(data) {
                return getIcon(data);
              }
            },
            {
              data: "timechecked",
              className: "text-center",
              render: function(data) {
                return ConvertDateFromDiv(Date.parse(data));
              }
            },
            {
              data: "courseinfo",
              render: function(data, type, row, meta) {
                var url = "/course/view.php?id=";

                if (data) {
                  var d = JSON.parse(data);
                  var courses = [];

                  d.forEach(function(course) {
                    var courseUrl, enrolled;

                    if (course.courseid && course.student_enrollment) {
                      courseUrl = url + course.courseid;
                      enrolled = course.student_enrollment;
                    } else {
                      courseUrl = "Course URL Undefined";
                      enrolled = 0;
                    }

                    var teachers = parseTeachers(course);

                    if (course) {
                      courses.push(
                        '<div><a href="' + courseUrl + '>"' + course.shortname + '</a> ' + enrolled + '</div>' +
                        '<div>' + teachers + '</div>' +
                        '<div class="mb-1"><a href="' + Config.wwwroot + '/mod/resource/view.php?id=' + course.instance_id + '>' + course.filename + '</a></div>'
                      );
                    } else {
                      courses.push(
                        "No course information found. This may be an invalid courseinfo entry within the block_filescan_files table."
                      );
                    }
                  });
                  return courses.join("<br>");
                } else {
                  return 'No response returned from the DataTables service. Are you sure the course exists?<br><strong>Ref id: ' + row["id"] + '</strong>';
                }
              }
            }
          ]
        });
      });
    }
  };
});

/**
 * Converts a unix timestamp to something that is human readable
 *
 * @param divTimeStr
 * @returns {string}
 * @constructor
 */
function ConvertDateFromDiv(divTimeStr) {
  //eg:-divTimeStr=18/03/2013 12:53:00
  var tmstr = divTimeStr.toString().split(" "); //'21-01-2013 PM 3:20:24'
  var dt = tmstr[0].split("/");
  var str = dt[2] + "/" + dt[1] + "/" + dt[0] + " " + tmstr[1]; //+ " " + tmstr[1]//'2013/01/20 3:20:24 pm'
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
