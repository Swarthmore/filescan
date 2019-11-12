module.exports = function (grunt) {

  "use strict";

  grunt.initConfig({

    filescan: {
      files: "amd/src/**/*.js"
    },

    jshint: {
      files: ["Gruntfile.js", "<% filescan.files %>"],
      options: {
        globals: {
          jQuery: true,
          console: true,
          module: true,
          document: true
        }
      }
    },

    requirejs: {
      build: {
        options: {
          appDir: "amd/src",
          baseUrl: "./",
          optimize: "none",
          dir: "amd/build"
        }
      }
    },

    watch: {
      files: ["<%= filescan.files %>"],
      tasks: ["jshint"]
    }

  });

  grunt.loadNpmTasks("grunt-contrib-watch");
  grunt.loadNpmTasks("grunt-contrib-requirejs");
  grunt.loadNpmTasks("grunt-contrib-jshint");

  grunt.registerTask("default", ["jshint", "requirejs"]);

};