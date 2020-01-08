const path = require("path");

module.exports = {
  mode: process.env.NODE_ENV || "production",

  entry: {
    afs: path.join(__dirname, "js", "index"),
    progressbars: path.join(__dirname, "js", "progressbars")
  },

  output: {
    path: path.resolve(__dirname, "amd/build"),
    filename: "[name].js",
    libraryTarget: "umd"
  },

  module: {
    rules: [
      {
        test: /\.(js|jsx)$/,
        exclude: /node_modules/,
        use: {
          loader: "babel-loader"
        }
      }
    ]
  },

  externals: {
    jquery: "jQuery"
  }
};
