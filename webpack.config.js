const path = require('path')

module.exports = {
    output: {
        path: path.join(__dirname, '/assets/js/dist'),
        filename: '[name].js',
    },
    entry: {
        registration: './assets/js/dev/registration.jsx',
    },
    module: {
        rules: [
            {
                test: /\.(js|jsx)$/,
                use: {
                    loader: 'babel-loader',
                    options: {
                        presets: ['@babel/preset-env', '@babel/preset-react']
                    }
                },
                exclude: /node_modules/,
            },
            {
                test: /\.css$/i,
                use: [ 'style-loader', 'css-loader']
            }
        ],
    }
}