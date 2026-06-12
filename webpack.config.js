const path = require('path')
const webpackConfig = require('@nextcloud/webpack-vue-config')
const webpackRules = require('@nextcloud/webpack-vue-config/rules')
const ESLintPlugin = require('eslint-webpack-plugin')

const buildMode = process.env.NODE_ENV
const isDev = buildMode === 'development'
webpackConfig.devtool = isDev ? 'cheap-source-map' : 'source-map'

webpackConfig.stats = {
	colors: true,
	modules: false,
}

const appId = 'raweditor'

webpackConfig.entry = {
	main: { import: path.join(__dirname, 'src', 'main.js'), filename: appId + '-main.js' },
	filesplugin: { import: path.join(__dirname, 'src', 'filesplugin.js'), filename: appId + '-filesplugin.js' },
}

webpackConfig.module.rules = Object.values(webpackRules)

if (isDev) {
	webpackConfig.plugins.push(
		new ESLintPlugin({
			extensions: ['js', 'vue'],
			files: 'src',
			failOnError: false,
		}),
	)
}

module.exports = webpackConfig
