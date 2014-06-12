<?php
use Aqua\BBCode\Smiley;
use Aqua\Core\L10n;

return array(
	'smiley_path' => \Aqua\URL . '/uploads/smiley/',
	'smiley_descriptions' => array_column(Smiley::smileys(), 'text'),
	'smiley_images' => array_column(Smiley::smileys(), 'file'),
	'removePlugins' => 'autogrow,bbcode,spoiler,pagebreak',
	'extraPlugins' => 'pagination',
	'height' => 450,
	'contentsLangDirection' => strtolower(L10n::$direction),
	'defaultLanguage' => L10n::$code,
	'pageSeparator' => '<!--- nextpage -->',
	'pageSeparatorPattern' => '(?:<!--+ *nextpage *--+>)',
	'enterMode' => 1,
	'toolbar' => 'AquaCore_Admin',
	'toolbar_AquaCore_Admin' => array(
		array(
			'name' => 'editing',
			'items' => array(
				'Cut', 'Copy',
				'-',
				'addPage',
				'-',
				'Find', 'Replace', 'SelectAll',
				'-',
				'Undo', 'Redo'
			)
		),
		array(
			'name' => 'clipboard',
			'items' => array( 'Paste', 'PasteText', 'PasteFromWord' )
		),
		array(
			'name' => 'insert',
			'items' => array(
				'Link', 'Unlink', 'Anchor',
				'-',
				'Smiley', 'Image', 'Flash',
				'-',
				'Table', 'HorizontalRule', 'SpecialChar', 'Iframe'
			)
		),
		array(
			'name' => 'view',
			'items' => array( 'Maximize', 'ShowBlocks', '-', 'Source' )
		),
		'/',
		array(
			'name' => 'basicstyles',
			'items' => array(
				'Bold', 'Italic', 'Underline', 'Strike',
				'-',
				'Subscript', 'Superscript',
				'-',
				'RemoveFormat'
			)
		),
		array(
			'name' => 'blocks',
			'items' => array(
				'NumberedList', 'BulletedList',
				'-',
				'Outdent', 'Indent',
				'-',
				'Blockquote', 'CreateDiv',
				'-',
				'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock',
				'-',
				'BidiLtr', 'BidiRtl'
			)
		),
		array(
			'name' => 'color',
			'items' => array( 'TextColor', 'BGColor' )
		),
		'/',
		array(
			'name' => 'styles',
			'items' => array( 'Styles' )
		),
		array(
			'name' => 'format',
			'items' => array( 'Format' )
		),
		array(
			'name' => 'format',
			'items' => array( 'Font' )
		),
		array(
			'name' => 'format',
			'items' => array( 'FontSize' )
		),
		array(
			'name' => 'about',
			'items' => array( 'About' )
		),
	)
);