function ConvertDateFromDiv(divTimeStr) {
    //eg:-divTimeStr=18/03/2013 12:53:00
    var tmstr = divTimeStr.toString().split(' '); //'21-01-2013 PM 3:20:24'
    var dt = tmstr[0].split('/');
    var str = dt[2] + "/" + dt[1] + "/" + dt[0] + " " + tmstr[1]; //+ " " + tmstr[1]//'2013/01/20 3:20:24 pm'
    var time = new Date(str);
    if (time == "Invalid Date") {
        time = new Date(divTimeStr);
    }
    return time.toLocaleDateString();
}

define(['jquery', 'theme_boost/dataTables'], ($, DataTable) => {
    return {
        make: (token) => {

            const domain = 'https://mappt.swarthmore.edu'
            const f = 'block_filescan_request_files'

            const api = domain + '/webservice/rest/server.php' + '?wstoken=' + token + '&wsfunction=' + f + '&moodlewsrestformat=' + 'json'

            $(document).ready(function () {
                $('#dt').DataTable({
                    "processing": true,
                    "serverSide": true,
                    "ajax": {
                        url: api,
                        type: 'POST'
                    },
                    "columnDefs": [{
                        "defaultContent": "NULL",
                        "targets": "_all"
                    }],
                    "columns": [
                        {"data": "id"},
                        {"data": "pagecount"},
                        {
                            "data": "status",
                            "render": (data) => {
                                if (data == 'check') {
                                    return '<i class="fa fa-check text-success fa-fw"></i>'
                                } else if (data == 'fail') {
                                    return '<i class="fa fa-exclamation text-warning fa-fw" aria-hidden="true"></i>'
                                } else if (data == 'error') {
                                    return '<i class="fa fa-times text-danger fa-fw" aria-hidden="true"></i>'
                                } else {
                                    return '<strong>Error</strong>' // catch all in case all else fails
                                }
                            }
                        },
                        {
                            "data": "checked",
                            "render": (data) => {
                                if (data) {
                                    return '<i class="fa fa-check text-success"></i>'
                                } else {
                                    return '<i class="fa fa-times text-danger"></i>'
                                }
                            }
                        },
                        {
                            "data": "hastext",
                            "render": (data) => {
                                if (data) {
                                    return '<i class="fa fa-check text-success"></i>'
                                } else {
                                    return '<i class="fa fa-times text-danger"></i>'
                                }
                            }
                        },
                        {
                            "data": "hastitle",
                            "render": (data) => {
                                if (data) {
                                    return '<i class="fa fa-check text-success"></i>'
                                } else {
                                    return '<i class="fa fa-times text-danger"></i>'
                                }
                            }
                        },
                        {
                            "data": "hasoutline",
                            "render": (data) => {
                                if (data) {
                                    return '<i class="fa fa-check text-success"></i>'
                                } else {
                                    return '<i class="fa fa-times text-danger"></i>'
                                }
                            }
                        },
                        {
                            "data": "haslanguage",
                            "render": (data) => {
                                if (data) {
                                    return '<i class="fa fa-check text-success"></i>'
                                } else {
                                    return '<i class="fa fa-times text-danger"></i>'
                                }
                            }
                        },
                        {
                            "data": "timechecked",
                            "render": (data, type, row, meta) => {
                                let d = Date.parse(data)
                                return ConvertDateFromDiv(d)
                            }
                        },
                        {
                            "data": "courseinfo",
                            "render": (data) => {
                                // parse the data from courseinfo and display a link to the courses
                                let url = '/course/view.php?id='

                                if (data) {
                                    let d = JSON.parse(data)
                                    let courses = []

                                    d.forEach(course => {
                                        let courseUrl = url + course.courseid
                                        let teachersObj = course.teachers // could have multiple teachers
                                        let teacher
                                        let enrolled = course.student_enrollment

                                        // check the teacher object
                                        if (teachersObj) {
                                            teacher =  Object.values(teachersObj)[0]

                                            if(!teacher){
                                                teacher = 'No teacher'
                                            } else {
                                                teacher = `<a href="#">${teacher.firstname} ${teacher.lastname}</a>`
                                            }
                                        }

                                        if (course) {
                                            courses.push(
                                                '<a href="' + courseUrl + '">' + course.shortname + '</a>' + '<br>' +
                                                teacher + '<br>' +
                                                enrolled + ' Students Enrolled' + '<br>' +
                                                `<strong><a href="https://mappt.swarthmore.edu/mod/resource/view.php?id=${course.file_id}">` + course.file_id + '</a></strong>'
                                            )
                                        } else {
                                            courses.push('NA')
                                        }
                                    })
                                    return courses.join('<hr>')
                                } else {
                                    return 'NA'
                                }
                            }
                        },
                        {
                            "data": "filepath",
                            "render": () => {
                                return '<i class="fa fa-file text-info fa-2x mt-2"></i>'
                            }
                        },
                        {
                            "data": "action",
                            "render": (data) => {
                                return (
                                    `<a class="btn btn-primary btn-block btn-sm mt-2" href="#" role="button">Fix</a>`
                                );
                            }
                        }
                    ],
                })
            })

        }
    }
})