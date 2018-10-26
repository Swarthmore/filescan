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

// todo: import datatables from npm or elsewhere
define(['jquery', 'theme_boost/dataTables'], ($, DataTable) => {
  return {
    make: (token) => {

        const domain = 'https://mappt.swarthmore.edu/'
        const accessToken = '08248df90017c46872d3d28faa495d01' // todo: move this to a configuration file
        const f = 'block_filescan_get_access_files'

      const api = domain + '/webservice/rest/server.php' + '?wstoken=' + accessToken + '&wsfunction=' + f + '&moodlewsrestformat=' + 'json'

      $(document).ready(function(){
        $('#mog').DataTable({
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
                { "data": "id" },
                { "data": "pagecount" },
                {
                    "data": "status",
                    "render": (data) => {
                        if (data == 'check') {
                            return '<i class="fa fa-check-circle text-success fa-fw"></i>'
                        } else if (data == 'fail') {
                            return '<i class="fa fa-exclamation-circle text-warning fa-fw" aria-hidden="true"></i>'
                        } else if (data == 'error') {
                            return '<i class="fa fa-times-circle text-danger fa-fw" aria-hidden="true"></i>'
                        }
                    }
                },
                {
                    "data": "checked",
                    "render": (data) => {
                        if(data) {
                            return '<i class="fa fa-check-circle text-success"></i>'
                        } else {
                            return '<i class="fa fa-times-circle text-danger"></i>'
                        }
                    }
                },
                {
                    "data": "hastext",
                    "render": (data) => {
                        if(data) {
                            return '<i class="fa fa-check-circle text-success"></i>'
                        } else {
                            return '<i class="fa fa-times-circle text-danger"></i>'
                        }
                    }
                },
                {
                    "data": "hastitle",
                    "render": (data) => {
                        if(data) {
                            return '<i class="fa fa-check-circle text-success"></i>'
                        } else {
                            return '<i class="fa fa-times-circle text-danger"></i>'
                        }
                    }
                },
                {
                    "data": "hasoutline",
                    "render": (data) => {
                        if(data) {
                            return '<i class="fa fa-check-circle text-success"></i>'
                        } else {
                            return '<i class="fa fa-times-circle text-danger"></i>'
                        }
                    }
                },
                {
                    "data": "haslanguage",
                    "render": (data) => {
                        if(data) {
                            return '<i class="fa fa-check-circle text-success"></i>'
                        } else {
                            return '<i class="fa fa-times-circle text-danger"></i>'
                        }
                    }
                },
                {
                    "data": "timechecked" ,
                    "render": (data, type, row, meta) => {
                        let d = Date.parse(data)
                        return ConvertDateFromDiv(d)
                    }
                },
                {
                    "data": "courseinfo",
                    "render": (data) => {
                        let url = '/course/view.php?id='

                        if (data) {
                            let d = JSON.parse(data)
                            let courses = []

                            d.forEach(course => {
                                let courseUrl = url + course.courseid

                                if (course) {
                                    courses.push('<a href="' + courseUrl + '">' + course.shortname + '</a>')
                                } else {
                                    courses.push('NA')
                                }
                            })
                            return courses.join('<br>')
                        } else {
                            return 'NA'
                        }
                    }
                }
            ],
        })
      })

    }
  }
})