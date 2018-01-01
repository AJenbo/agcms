const MinifyPlugin = require("babel-minify-webpack-plugin");

module.exports = {
    entry: {
        "admin": "./source/javascript/admin/index.js",
        "adminExplorer": "./source/javascript/admin/explorer.js",
        "adminUpload": "./source/javascript/admin/upload.js",
        "adminImageEdit": "./source/javascript/admin/image-edit.js",
        "adminAddList": "./source/javascript/admin/addList.js",
        "site": "./source/javascript/site.js",
        "arms-gallery.dk": "./application/theme/arms-gallery.dk/javascript/javascript.js",
        "huntershouse.dk": "./application/theme/huntershouse.dk/javascript/javascript.js"
    },
    output: {filename: "[name].js", path: __dirname + "/application/javascript"},
    plugins: [new MinifyPlugin({}, {})],
    module: {
        rules:
            [{test: /\.js$/, exclude: /node_modules/, use: {loader: "babel-loader", options: {"presets": ["env"]}}}]
    }
};
