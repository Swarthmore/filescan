module.exports = function (grunt) {

  "use strict";

  require("grunt-load-gruntfile")(grunt);

  grunt.loadNpmTasks("grunt-contrib-watch");
  grunt.loadNpmTasks("grunt-contrib-requirejs");
  grunt.loadNpmTasks("grunt-contrib-jshint");

  grunt.initConfig({

    jshint: {
      files: ["Gruntfile.js", "amd/src/**/*.js"],
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
      files: ["amd/src/**/*.js"],
      tasks: ["jshint"]
    }

  });

  grunt.registerTask("default", ["jshint", "requirejs"]);

};
