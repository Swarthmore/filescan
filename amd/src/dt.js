// once these files are moved locally 
// require.config({
//   paths: {
//     "jquery": "https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js",
//     "dataTables.net": "https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"
//   },
//   shim: {
//     "jquery": {
//       exports: "$"
//     },
//     "datatables.net": {
//       depts: ["jquery"]
//     }
//   }
// });

define(['jquery', 'core/ajax', 'theme_boost/datatables'], function ($, ajax, datatables){

  return {
    init: function () {

      $(document).ready(function () {
        
        // initialize the data table
        var view = $('#view').DataTable({
          "processing": true,
          "serverSide": true,
          "ajax": function(data, callback, settings) {
            ajax.call([
              {
                methodname: "block_filescan_request_files",
                args: data,
                done: function(res){
                  callback(res);
                },
                fail: function(){
                  console.error("Could not get data");
                }
              }
            ])
          },
          "columnDefs": [
            {
              "targets": "id",
              "visible": false
            }
          ],
          "columns": [
            {
              "data": "status",
              "className": "text-center",
              "render": function(data){ 
                getStatusIcon(data) 
              }
            },
            {
              "data": "hastext",
              "className": "text-center",
              "render": function(data){ 
                getIcon(data) 
              }
            },
            {
              "data": "hastitle",
              "className": "text-center",
              "render": function(data){ 
                getIcon(data) 
              }
            },
            {
              "data": "hasoutline",
              "className": "text-center",
              "render": function(data){ 
                getIcon(data) 
              }
            },
            {
              "data": "haslanguage",
              "className": "text-center",
              "render": function(data){ 
                getIcon(data) 
              }
            },
            {
              "data": "timechecked",
              "className": "text-center",
              "render": function(data){ 
                return ConvertDateFromDiv(Date.parse(data)) 
              }
            },
            {
              "data": "courseinfo",
              "render": (data, type, row, meta) => {
                  let url = '/course/view.php?id='

                  if (data) {
                      let d = JSON.parse(data)
                      let courses = []

                      d.forEach(course => {
                          let courseUrl = url + course.courseid
                          let enrolled = course.student_enrollment

                          let teachers = parseTeachers(course)

                          if (course) {
                            courses.push(
                              `<div><a href="${courseUrl}">${course.shortname}</a> [${enrolled}]</div>` +
                              `<div>${teachers}</div>` +
                              `<div class="mb-1"><a href="${mdlcfg.wwwroot}/mod/resource/view.php?id=${course.instance_id}">${course.filename}</a></div>`
                              ); 
                          } else {
                              courses.push('No course information found. This may be an invalid courseinfo entry within the block_filescan_files table.')
                          }
                      })
                      return courses.join('<br>')
                  } else {
                      return `No response returned from the DataTables service. Are you sure the course exists?<br><strong>Ref id: ${row['id']}</strong>`
                  }
              }
          }
            ]
          })
          
        })
        
      }
    }

})

/**
* Converts a unix timestamp to something that is human readable
*
* @param divTimeStr
* @returns {string}
* @constructor
*/
function ConvertDateFromDiv(divTimeStr) {
  //eg:-divTimeStr=18/03/2013 12:53:00
  var tmstr = divTimeStr.toString().split(' ') //'21-01-2013 PM 3:20:24'
  var dt = tmstr[0].split('/')
  var str = dt[2] + "/" + dt[1] + "/" + dt[0] + " " + tmstr[1] //+ " " + tmstr[1]//'2013/01/20 3:20:24 pm'
  var time = new Date(str)
  
  if (time == "Invalid Date") {
    time = new Date(divTimeStr)
  }
  
  return time.toLocaleDateString()
}
/**
* Returns a success icon if true, and a danger icon if not
*
* @param bool
* @returns {string}
*/
function getIcon(bool) {
  if (checkResponse(status)){
    if(bool){
      return '<i class="fa fa-check text-success fa-fw"></i>'
    } else {
        return '<i class="fa fa-times text-danger fa-fw"></i>'
    }
  } else {
    return "Server response is invalid."
  }
}
/**
* Returns an icon clause
*
* @param status
* @returns {string}
*/
function getStatusIcon(status) {
  if (checkResponse(status)) {

    if(status == 'check') {
      return '<i class="fa fa-exclamation text-warning fa-fw"></i>'
    } else if(status == 'fail') {
        return '<i class="fa fa-exclamation-triangle text-warning fa-fw"></i>'
    } else if (status == 'error') {
        return '<i class="fa fa-times text-danger fa-fw"></i>'
    } else if (status == 'pass') {
        return '<i class="fa fa-check text-success fa-fw"></i>'
    }
  } else {
    return "Server response is invalid."
  }
}
/**
* Parse teachers out of the courseinfo object
*
* @param course
* @returns {Array}
*/
function parseTeachers(course) {
  var teachers = ''
  
  // This section of code may need to be transpiled to be compatible with Moodle
  Object.entries(course).forEach(([key, val]) => {
    if (key === 'teachers') {
      Object.entries(val).forEach(([teacherId, teacherInfo]) => {
        teachers += teacherInfo.firstname + ' ' + teacherInfo.lastname + '<br>'
      })
    }
  })
  
  return teachers
}

function checkResponse(res){
  if(res){
    return true;
  } else {
    return false;
  }
}