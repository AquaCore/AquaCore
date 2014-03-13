<?php
$smileys = include \Aqua\ROOT . '/settings/smiley.php';
$page->theme
	->addSettings('ckeComments',array(
		'font_sizes'            => 'Georgia/Georgia, serif;' .
		                           'Palatino/"Palatino Linotype", "Book Antiqua", Palatino, serif;' .
		                           'Times New Roman/"Times New Roman", Times, serif;' .
		                           'Arial/Arial, Helvetica, sans-serif;' .
		                           'Helvetica/Helvetica, sans-serif;' .
		                           'Arial Black/"Arial Black", Gadget, sans-serif;' .
		                           'Comic Sans MS/"Comic Sans MS", cursive, sans-serif;' .
		                           'Impact/Impact, Charcoal, sans-serif;' .
		                           'Lucida Sans/"Lucida Sans Unicode", "Lucida Grande", sans-serif;' .
		                           'Tahoma/Tahoma, Geneva, sans-serif;' .
		                           'Trebuchet MS/"Trebuchet MS", Helvetica, sans-serif;' .
		                           'Verdana/Verdana, Geneva, sans-serif;' .
		                           'Courier New/"Courier New", Courier, monospace;' .
		                           'Lucida Console/"Lucida Console", Monaco, monospace;',
		'fontSize_sizes'        => '25%;50%;75%;100%;125%;175%;200%;225%;275%;300%;',
		'basicEntities'         => false,
		'entities'              => false,
		'fillEmptyBlocks'       => false,
		'forcePasteAsPlainText' => true,
		'smiley_path'           => \Aqua\URL . '/uploads/smiley/',
		'smiley_descriptions'   => array_keys($smileys),
		'smiley_images'         => array_values($smileys),
		'removePlugins'         => 'autogrow,pagination',
		'extraPlugins'          => 'bbcode',
		'bbCodeTags'            => 'b,s,u,i,' .
		                           'sub,sup,' .
		                           'url,email,img,' .
		                           'color,background,' .
		                           'size,font,' .
		                           'indent,center,right,justify' .
		                           'hide,spoiler,acronym,list',
		'height'                => 100,
		'enterMode'             => 2,
		'allowedContent'        => false,
		'toolbar'               => array(
			array(
				'name'  => 'editing',
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
				'name'  => 'clipboard',
				'items' => array( 'Paste', 'PasteText', 'PasteFromWord' )
			),
			array(
				'name'  => 'insert',
				'items' => array(
					'Link', 'Unlink',
					'-',
					'Smiley', 'Image',
				)
			),
			array(
				'name'  => 'view',
				'items' => array( 'Maximize', '-', 'Source' )
			),
			'/',
			array(
				'name'  => 'basicstyles',
				'items' => array(
					'Bold', 'Italic', 'Underline', 'Strike',
					'-',
					'Subscript', 'Superscript',
					'-',
					'RemoveFormat'
				)
			),
			array(
				'name'  => 'blocks',
				'items' => array(
					'NumberedList', 'BulletedList',
					'-',
					'Outdent', 'Indent',
					'-',
					'Spoiler',
					'-',
					'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock',
				)
			),
			array(
				'name'  => 'color',
				'items' => array( 'TextColor', 'BGColor' )
			),
			array(
				'name'  => 'format',
				'items' => array( 'Font' )
			),
			array(
				'name'  => 'format',
				'items' => array( 'FontSize' )
			)
		)
	));
