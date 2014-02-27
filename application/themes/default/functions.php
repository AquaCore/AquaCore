<?php
use Aqua\Core\App;
use Aqua\UI\ScriptManager;
/**
 * @var $this \Aqua\UI\Theme
 */
$this->footer->enqueueScript(ScriptManager::script('aquacore.flash'), false);
$json = json_encode(App::user()->getFlash());
$this->footer->enqueueScript('theme.flash')
	->type('text/javascript')
	->append("
AquaCore._flash = new AquaCore.Flash();
AquaCore._flash.enqueue($json);
");
