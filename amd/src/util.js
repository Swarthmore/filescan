// Helper function to convert bytes to megabytes
export const sizeInMb = sizeInBytes => (sizeInBytes / (1024 * 1024)).toFixed(2);

// Helper function to convert an HTML table into a CSV
export const downloadAsCSV = (tableEle, separator = ',') => {
    let csvRows = [];
    // Only get direct children of the table in question (thead, tbody)
    Array.from(tableEle.children).forEach(function(node) {
        // Using scope to only get direct tr of node
        node.querySelectorAll(':scope > tr').forEach(function(tr) {
            let csvLine = [];
            // Again scope to only get direct children
            tr.querySelectorAll(':scope > td').forEach(function(td) {
                // Clone as to not remove anything from original
                let copytd = td.cloneNode(true);
                let data;
                if (copytd.dataset.val) {
                    data = copytd.dataset.val.replace(/(\r\n|\n|\r)/gm, '');
                } else {
                    Array.from(copytd.children).forEach(function(remove) {
                        // Remove nested elements before getting text
                        remove.parentNode.removeChild(remove);
                    });
                    data = copytd.textContent.replace(/(\r\n|\n|\r)/gm, '');
                }
                data = data.replace(/(\s\s)/gm, ' ').replace(/"/g, '""');
                csvLine.push('"' + data + '"');
            });
            csvRows.push(csvLine.join(separator));
        });
    });
    let a = document.createElement("a");
    a.style = "display: none; visibility: hidden"; // Safari needs visibility hidden
    a.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csvRows.join('\n'));
    a.download = 'testfile.csv';
    document.body.appendChild(a);
    a.click();
    a.remove();
};

// Helper function to render fontawesome success icon
export const renderSuccessIcon = () => '<i class="fa-solid fa-check text-success"></i>';

// Helper function render fontawesome warning icon
export const renderWarningIcon = () => '<i class="fa-solid fa-warning text-warning"></i>';

// Helper function to render fontawesome failure icon
export const renderFailIcon = () => '<i class="fa-solid fa-xmark text-danger"></i>';