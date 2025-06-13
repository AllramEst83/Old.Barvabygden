const defaultConfig = require("@wordpress/scripts/config/webpack.config");
const path = require("path");
const CopyWebpackPlugin = require("copy-webpack-plugin");
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const CssMinimizerPlugin = require("css-minimizer-webpack-plugin");

module.exports = {
	...defaultConfig,
	cache: {
		type: "filesystem", // Enable filesystem caching for faster builds
	},

	entry: {
		"oc-plugins-page": "./src/PluginsPage.js", // Plugins Page
		"oc-advance-error-page": './modules/error-page/src/ErrorPage.js'
	},

	output: {
		path: path.resolve(__dirname, "./assets/js/block-scripts/"), // Common output path
		filename: "[name].js",
	},

	mode: "production", // Ensure minification

	externals: {
		react: "React", // Use WordPress’s React
		"react-dom": "ReactDOM", // Use WordPress’s ReactDOM
		"@wordpress/components": "wp.components",
		"@wordpress/element": "wp.element",
		"@wordpress/data": "wp.data",
		"@wordpress/i18n": "wp.i18n",
		"@wordpress/api-fetch": "wp.apiFetch",
	},

	module: {
		rules: [
			{
				test: /\.js$/, // Process JavaScript and JSX files
				exclude: /node_modules/,
				use: {
					loader: "babel-loader",
					options: {
						presets: ["@babel/preset-env", "@babel/preset-react"], // Ensure React JSX support
					},
				},
			},
			{
				test: /\.css$/,
				use: [MiniCssExtractPlugin.loader, "css-loader"], // Process CSS files
			},
		],
	},

	optimization: {
		minimizer: [
			`...`, // Keep existing minimizers (JS, etc.)
			new CssMinimizerPlugin(), // Minifies CSS
		],
	},

	plugins: [
		new MiniCssExtractPlugin({
			filename: "../css/[name].min.css", // Output minified CSS
		}),
		new CopyWebpackPlugin({
			patterns: [
				{
					from: path.resolve(__dirname, 'node_modules/@group.one/gravity/dist/css/brands/one.css'),
					to: path.resolve(__dirname, './assets/min-css/one.min.css'), // Minified file output
					noErrorOnMissing: false,
				},
			],
		}),
	],
};