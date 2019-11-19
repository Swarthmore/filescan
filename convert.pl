# This script converts a DataTables javascript file to work nicely with the
# Moodle AMD scheme. It adds some comments that hide warnings from jshint that
# would otherwise appear when compiling with grunt. It also removes any explicit
# 'datatables' module name (since that's not what it'll be named within Moodle)
# and changes dependencies to reflect the Moodle structure.

print "/* jshint unused:false, newcap:false, maxlen:10000 */\n";
print "/* eslint-disable */";
print "/* globals require:false, jQuery:false */\n"; 
while (<>) {
    # convert dependency from 'datatables' to 'block_filescan/jquery.dataTables'
    s@define\((\s*)\['jquery',(\s*)'datatables\.net'\]@define(['jquery','block_filescan/jquery.dataTables']@g;

    print;
}
