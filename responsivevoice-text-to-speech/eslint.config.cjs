/**
 * Extends the @wordpress/scripts default flat config with two project rules:
 * WordPress runtime externals and Node tooling scripts.
 */
const defaultConfig = require( '@wordpress/scripts/config/eslint.config.cjs' );

module.exports = [
	...defaultConfig,

	// @wordpress/* packages are provided by WordPress at runtime and
	// externalised by the build, so they are not project dependencies.
	{
		settings: {
			'import/core-modules': [
				'@wordpress/blocks',
				'@wordpress/block-editor',
				'@wordpress/components',
				'@wordpress/element',
				'@wordpress/i18n',
			],
		},
	},

	// Node build and release scripts: console output is the whole point,
	// and they are not authored to the shipped-source JSDoc standard.
	{
		files: [ 'scripts/**/*.mjs' ],
		rules: {
			'no-console': 'off',
			'no-nested-ternary': 'off',
			'jsdoc/require-param-type': 'off',
		},
	},

	// Local config files may import dev tooling directly.
	{
		files: [ '*.cjs', '*.mjs', '.prettierrc.js', 'eslint.config.cjs' ],
		rules: {
			'import/no-extraneous-dependencies': 'off',
		},
	},
];
