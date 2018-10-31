define(['core/chartjs', 'jquery', 'theme_boost/dataTables'], (Chart, $) => {

    let colors = {
        pass: 'rgba(76, 175, 80, 1)',
        check: 'rgba(255, 193, 7, 1)',
        error: 'rgba(244, 67, 54, 1)',
        fail: 'rgba(121, 85, 72, 1)',
        cyan: 'rgba(0, 188, 212, 1)',
        lightBlue: 'rgba(3, 169, 244, 1)',
        teal: 'rgba(0, 150, 136, 1)',
        indigo: 'rgba(63, 81, 181, 1)',
        green: 'rgba(76, 175, 80, 1)'
    }

    return {
        draw: (passing, checks, errors, failures,
               hasText, hasTitle, hasOutline, hasLanguage) => {

            // Set up the app
            let c1 = document.createElement('canvas')
            let ctx1 = c1.getContext('2d')

            let c2 = document.createElement('canvas')
            let ctx2 = c2.getContext('2d')

            document.getElementById('c1').appendChild(c1)
            document.getElementById('c2').appendChild(c2)

            // Set up the overall view
            let myChart = new Chart(ctx1, {
                type: 'horizontalBar',
                data: {
                    labels: ['Pass', 'Check', 'Error', 'Fail'],
                    datasets: [{
                        label: '# Of Files',
                        data: [passing, checks, errors, failures],
                        backgroundColor: [colors.pass, colors.check, colors.error, colors.fail]
                    }]
                }
            })

            let hasStuff = new Chart(ctx2, {
                type: 'horizontalBar',
                data: {
                    labels: ['Has Text', 'Has Title', 'Has Outline', 'Has Language'],
                    datasets: [{
                        label: 'File Has Breakdown',
                        data: [hasText, hasTitle, hasOutline, hasLanguage],
                        backgroundColor: [colors.cyan, colors.lightBlue, colors.teal, colors.indigo]
                    }]
                }
            })

        }

    }

})