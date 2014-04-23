<?php
use Aqua\UI\ScriptManager;
use Aqua\UI\StyleManager;

StyleManager::register('jquery-ui')
	->href(\Aqua\URL . '/assets/scripts/jquery-ui/jquery-ui.css');
StyleManager::register('bbcode')
	->href(\Aqua\URL . '/assets/styles/bbcode.css');
StyleManager::register('codemirror')
	->href(\Aqua\URL . '/assets/scripts/codemirror/codemirror.css');

ScriptManager::register('number-format')
	->src(\Aqua\URL . '/assets/scripts/number-format.js');
ScriptManager::register('sprintf')
	->src(\Aqua\URL . '/assets/scripts/sprintf/sprintf.min.js');
// CodeMirror
ScriptManager::register('codemirror')
	->src(\Aqua\URL . '/assets/scripts/codemirror/codemirror.min.js')
	->stylesheet('codemirror');
// HighSoft
ScriptManager::register('highsoft.highchart')
	->src(\Aqua\URL . '/assets/scripts/highsoft/highcharts.js');
ScriptManager::register('highsoft.highchart-more')
	->src(\Aqua\URL . '/assets/scripts/highsoft/highcharts-more.js')
	->dependsOn('highsoft.highchart');
ScriptManager::register('highsoft.highstock')
	->src(\Aqua\URL . '/assets/scripts/highsoft/highstock.js');
// CKEditor
ScriptManager::register('ckeditor')
	->src(\Aqua\URL . '/assets/scripts/ckeditor/ckeditor.js');
ScriptManager::register('ckeditor')
	->src(\Aqua\URL . '/assets/scripts/ckeditor/ckeditor.js');
ScriptManager::register('ckeditor-i18n')
	->src(\Aqua\URL . '/assets/scripts/ckeditor/lang/:language.js')
	->dependsOn(array( 'ckeditor' ));
// jQuery v2.0.3
ScriptManager::register('jquery')
	->src(\Aqua\URL . '/assets/scripts/jquery/jquery-1.11.0.min.js');
// jQuery UI v1.10.3
ScriptManager::register('jquery-ui')
	->src(\Aqua\URL . '/assets/scripts/jquery-ui/jquery-ui-1.10.3.min.js')
	->language(\Aqua\URL . '/assets/scripts/jquery-ui/i18n/%s.js', 'en')
	->dependsOn(array( 'jquery' ));
// Moment
ScriptManager::register('moment')
	->src(\Aqua\URL . '/assets/scripts/moment/moment.min.js')
	->language(\Aqua\URL . '/assets/scripts/moment/lang/%s.js', 'en');
// Autosize
ScriptManager::register('jquery.autosize')
	->src(\Aqua\URL . '/assets/scripts/autosize/jquery.autosize.min.js')
	->dependsOn(array( 'jquery' ));
// Timepicker
ScriptManager::register('jquery-ui.timepicker')
	->src(\Aqua\URL . '/assets/scripts/timepicker/jquery-ui.timepicker.js')
	->language(\Aqua\URL . '/assets/scripts/timepicker/i18n/%s.js', 'en')
	->dependsOn(array( 'jquery-ui' ));
// AquaCore
ScriptManager::register('aquacore')
	->src(\Aqua\URL . '/assets/scripts/aquacore/aquacore.min.js')
	->dependsOn(array(
			'number-format',
			'sprintf',
			'jquery',
			'jquery-ui',
		))
	->compliesWith(array(
			'aquacore.aquacore',
			'aquacore.build-url',
			'aquacore.experience-slider',
			'aquacore.ajax-form',
			'aquacore.flash',
			'aquacore.cart',
			'aquacore.rating'
		));
ScriptManager::register('aquacore.aquacore')
	->src(\Aqua\URL . '/assets/scripts/aquacore/aquacore.js')
	->dependsOn(array( 'jquery', 'sprintf' ));
ScriptManager::register('aquacore.build-url')
	->src(\Aqua\URL . '/assets/scripts/aquacore/build-url.js')
	->dependsOn(array( 'aquacore.aquacore' ));
ScriptManager::register('aquacore.experience-slider')
	->src(\Aqua\URL . '/assets/scripts/aquacore/experience-slider.js')
	->dependsOn(array( 'aquacore.aquacore', 'jquery-ui', 'number-format' ));
ScriptManager::register('aquacore.ajax-form')
	->src(\Aqua\URL . '/assets/scripts/aquacore/ajax-form.js')
	->dependsOn(array( 'aquacore.aquacore', 'jquery-ui', 'number-format' ));
ScriptManager::register('aquacore.flash')
	->src(\Aqua\URL . '/assets/scripts/aquacore/flash.js')
	->dependsOn(array( 'aquacore.aquacore', 'jquery-ui' ));
ScriptManager::register('aquacore.cart')
	->src(\Aqua\URL . '/assets/scripts/aquacore/cart.js')
	->dependsOn(array( 'aquacore.aquacore', 'aquacore.build-url', 'jquery-ui' ));
ScriptManager::register('aquacore.rating')
	->src(\Aqua\URL . '/assets/scripts/aquacore/rating.js')
	->dependsOn(array( 'aquacore.aquacore', 'aquacore.build-url' ));
ScriptManager::register('aquacore.content')
	->src(\Aqua\URL . '/assets/scripts/aquacore/content.js')
	->dependsOn(array( 'codemirror', 'jquery' ));
