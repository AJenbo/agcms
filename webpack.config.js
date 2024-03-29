var webpack = require('webpack');

module.exports = {
    entry: {
        "admin": "./source/javascript/admin/index.js",
        "adminExplorer": "./source/javascript/admin/explorer.js",
        "adminUpload": "./source/javascript/admin/upload.js",
        "adminImageEdit": "./source/javascript/admin/image-edit.js",
        "adminAddList": "./source/javascript/admin/addList.js",
        "site": "./source/javascript/site.js",
        "arms-gallery.dk": "./source/javascript/arms-gallery.dk.js",
        "huntershouse.dk": "./source/javascript/huntershouse.dk.js"
    },
    output: {filename: "[name].js", path: __dirname + "/application/javascript"},
    devtool: false,
    plugins: [new webpack.SourceMapDevToolPlugin({
       filename: 'sourcemaps/[file].map',
       fileContext: 'public'
    })],
    module: {
        rules: [{
            test: /\.js$/,
            exclude: /node_modules/,
            use: {loader: "babel-loader", options: {"presets": ["@babel/preset-env"]}}
        }]
    }
};
