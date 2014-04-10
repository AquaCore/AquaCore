<?php
/**
 * @var $schedule      array
 * @var $castles       array
 * @var $schedule_form \Aqua\UI\Form
 * @var $castles_form  \Aqua\UI\Form
 * @var $page          \Page\Admin\Ragnarok\Server
 */

use Aqua\UI\Sidebar;
use Aqua\UI\ScriptManager;

$page->theme->template = 'sidebar-right';
$page->theme->addWordGroup('ragnarok-charmap', array( 'castle-id', 'castle-name' ));
$page->theme->footer->enqueueScript(ScriptManager::script('jquery-ui'));
$page->theme->footer->enqueueScript('theme.castles')
	->type('text/javascript')
	->src($page->theme->url . '/scripts/castle.js');

$sidebar = new Sidebar;
ob_start();
?>
<div class="ac-form-warning"><?php echo $schedule_form->field('name')->getWarning() ?></div>
<?php
echo $schedule_form->field('name')->render();
$sidebar->append('name', array(array(
	'title' => $schedule_form->field('name')->getLabel(),
    'content' => ob_get_contents()
	)));
ob_clean();
?>
<div class="ac-form-warning"><?php echo $schedule_form->field('castles')->getWarning() ?></div>
<?php
echo $schedule_form->field('castles')->render(),
	 '<small>', $schedule_form->field('castles')->getDescription(), '</small>';
$sidebar->append('castles', array(array(
	'title' => $schedule_form->field('castles')->getLabel(),
    'content' => ob_get_contents()
	)));
ob_clean();
?>
<div class="ac-form-warning"><?php echo $schedule_form->field('startday')->getWarning() ?></div>
<?php
echo $schedule_form->field('startday')->render();
$sidebar->append('startday', array(array(
	'title' => $schedule_form->field('startday')->getLabel(),
    'content' => ob_get_contents()
	)));
ob_clean();
?>
<div class="ac-form-warning"><?php echo $schedule_form->field('starttime')->getWarning() ?></div>
<?php
echo $schedule_form->field('starttime')->render();
$sidebar->append('starttime', array(array(
	'title' => $schedule_form->field('starttime')->getLabel(),
    'content' => ob_get_contents()
	)));
ob_clean();
?>
<div class="ac-form-warning"><?php echo $schedule_form->field('endday')->getWarning() ?></div>
<?php
echo $schedule_form->field('endday')->render();
$sidebar->append('endday', array(array(
	'title' => $schedule_form->field('endday')->getLabel(),
    'content' => ob_get_contents()
	)));
ob_clean();
?>
<div class="ac-form-warning"><?php echo $schedule_form->field('endtime')->getWarning() ?></div>
<?php
echo $schedule_form->field('endtime')->render();
$sidebar->append('endtime', array(array(
	'title' => $schedule_form->field('endtime')->getLabel(),
    'content' => ob_get_contents()
	)))->append('submit', array('class' => 'ac-sidebar-action', array(
		'content' => '<input class="ac-sidebar-submit" type="submit" value="' . __('application', 'submit') .'">'
	)));
ob_end_clean();
$sidebar->wrapper($schedule_form->buildTag());
$page->theme->set('sidebar', $sidebar);
?>
<table class="ac-table">
	<thead>
		<tr>
			<td colspan="9"></td>
		</tr>
		<tr class="alt">
			<td><input type="checkbox" ac-checkbox-toggle="schedule[]"</td>
			<td><?php echo __('ragnarok', 'id') ?></td>
			<td><?php echo __('ragnarok-charmap', 'schedule-name') ?></td>
			<td><?php echo __('ragnarok', 'castles') ?></td>
			<td><?php echo __('ragnarok-charmap', 'schedule-startday') ?></td>
			<td><?php echo __('ragnarok-charmap', 'schedule-starttime') ?></td>
			<td><?php echo __('ragnarok-charmap', 'schedule-endday') ?></td>
			<td><?php echo __('ragnarok-charmap', 'schedule-endtime') ?></td>
			<td><?php echo __('application', 'action') ?></td>
		</tr>
	</thead>
	<tbody>
	<?php if(empty($schedule)) : ?>
		<tr><td class="ac-table-no-result" colspan="9"><?php echo __('application', 'no-search-results') ?></td></tr>
	<?php else : foreach($schedule as $id => $time) : ?>
		<tr>
			<td><input type="checkbox" name="schedule[]" value="<?php echo $id ?>"></td>
			<td><?php echo $id ?></td>
			<td><?php echo htmlspecialchars($time['name']) ?></td>
			<td><?php
				$scheduleCastles = array();
				foreach($time['castles'] as $castleId) {
					$scheduleCastles[] = $page->charmap->castleName($castleId) ?: $castleId;
				}
				echo implode(', ', $scheduleCastles);
				?>
			</td>
			<td><?php echo __('week', $time['start_day']) ?></td>
			<td><?php echo $time['start_time'] ?></td>
			<td><?php echo __('week', $time['end_day']) ?></td>
			<td><?php echo $time['end_time'] ?></td>
			<td></td>
		</tr>
	<?php endforeach; endif;?>
	</tbody>
	<tfoot><tr><td colspan="9"></td></tr></tfoot>
</table>
<form method="POST">
<table class="ac-castles ac-settings-form">
	<tr>
		<td colspan="3">
			<div class="ac-settings-section">
				<div class="title"><?php echo __('ragnarok', 'castles') ?></div>
				<div class="separator"></div>
			</div>
		</td>
	</tr>
	<?php if($castles_form->message) : ?>
		<tr>
			<td colspan="3" class="ac-form-error">
				<div><?php echo $castles_form->message ?></div>
			</td>
		</tr>
	<?php endif; ?>
	<?php
	if(($ids = $page->request->getArray('casid', null)) &&
	   ($names = $page->request->getArray('casname', null)) &&
	   count($ids) === count($names)) {
		$count = count($ids);
		$castles = array();
		for($i = 0; $i < $count; ++$i) {
			if($ids[$i] !== '' && $names[$i] !== '') {
				$castles[(int)$ids[$i]] = $names[$i];
			}
		}
	}
	foreach($castles as $id => $name) :	?>
		<tr>
			<td class="ac-castle-id">
				<input type="number"
			           min="0"
			           placeholder="<?php echo __('ragnarok-charmap', 'castle-id') ?>"
			           name="casid[]"
			           value="<?php echo htmlspecialchars($id) ?>">
			</td>
			<td class="ac-castle-name">
				<input type="text"
			           placeholder="<?php echo __('ragnarok-charmap', 'castle-name') ?>"
			           name="casname[]"
			           value="<?php echo htmlspecialchars($name) ?>">
			</td>
			<td class="ac-castle-options">
				<button class="ac-delete-button" type="button" tabindex="-1"></button>
			</td>
		</tr>
	<?php endforeach; for($i = 0; $i < 5; ++$i) : ?>
		<tr class="empty">
			<td class="ac-castle-id">
				<input type="number"
				       min="0"
				       placeholder="<?php echo __('ragnarok-charmap', 'castle-id') ?>"
				       name="casid[]">
			</td>
			<td class="ac-castle-name">
				<input type="text"
				       placeholder="<?php echo __('ragnarok-charmap', 'castle-name') ?>"
				       name="casname[]">
			</td>
			<td class="ac-castle-options">
				<button class="ac-delete-button" type="button" tabindex="-1"></button>
			</td>
		</tr>
	<?php endfor; ?>
	<tr><td colspan="3"><button class="disabled ac-add-castle" type="button"><?php echo __('ragnarok-charmap', 'add-castle') ?></button></td></tr>
	<tr><td colspan="3">
			<div class="wrapper">
			<input class="ac-button" type="submit" value="<?php echo __('application', 'submit') ?>" name="setcastles">
			</div>
		</td></tr>
</table>
</form>
