# Moodle Accessibility File Scan Block
Moodle block to check PDFs for accessibility.  

This Moodle block consists of two main functions: 
* A scheduled task that looks for PDFs within courses, sends them to an external server to check for accessibilty, and saves the results to the Moodle database
* A Moodle block that displays the accessibility status of the PDF files within the course.  On the Moodle course page, the block displays a summary with a link to a detailed view with all the files in the course. 

# Requirements
* Moodle 3.1+
* A server to process files: https://github.com/Swarthmore/filescan-server.  This software can be installed on the Moodle server.


# Installation
* Clone or download this repository into the `<moodle root>/blocks` directory
* Rename the directory from `block-filescan` to `filescan`
* Log into Moodle as an admin and update Moodle to add the block

# Configuration
This plugin configuration requires a URL for the File Scan Server.  

By default, the scheduled task is set to scan an additional set of files every 5 minutes.  This can be adjusted in the Server --> Scheduled Tasks screen.  

There are configurations for help links for each type of accessibility problems.  These links can be set to specific URL's for remiation of the file.


