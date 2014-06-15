<?php
use Aqua\UI\ScriptManager;
use Aqua\UI\Form;
/**
 * @var $plugins      \Aqua\Plugin\Plugin[]
 * @var $plugin_count int
 * @var $upload       \Aqua\UI\Form
 * @var $paginator    \Aqua\UI\Pagination
 * @var $page         \Page\Admin\Plugin
 */

$page->theme->addWordGroup('plugin', array(
		'plugin-settings',
		'confirm-delete-s',
		'confirm-delete-p'
	));
$page->theme->footer->enqueueScript(ScriptManager::script('aquacore.ajax-form'));
$page->theme->footer->enqueueScript('theme.form-functions')
	->type('text/javascript')
	->src($page->theme->url . '/scripts/ajax-form-functions.js');
$page->theme->footer->enqueueScript('theme.plugin')
	->type('text/javascript')
	->src($page->theme->url . '/scripts/plugin.js');
$i = 0;
$settings_action = ac_build_url(array(
		'path' => array( 'plugin' ),
		'action' => 'settings',
		'arguments' => array( '' )
	));
$settings = '';
?>
<form method="POST" id="plugin-form" enctype="multipart/form-data">
	<table class="ac-table ac-plugin-table">
		<thead>
			<tr>
				<td colspan="6">
					<div style="float: left">
						<?php
						echo $upload->field('import')->render(),
							 $upload->field('submit')->render(), '<br/>',
							 $upload->field('import')->getDescription();
						?>
					</div>
					<div style="float: right; line-height: 3.5em">
						<select name="action">
							<option value="activate"><?php echo __('plugin', 'activate') ?></option>
							<option value="deactivate"><?php echo __('plugin', 'deactivate') ?></option>
							<option value="delete"><?php echo __('plugin', 'delete') ?></option>
						</select>
						<input type="submit" name="x-bulk" value="<?php echo __('application', 'apply') ?>" ac-default-submit="1">
					</div>
				</td>
			</tr>
			<tr class="alt">
				<td style="width: 30px; text-align: center"><input type="checkbox" ac-checkbox-toggle="plugins[]"></td>
				<td><?php echo __('plugin', 'name') ?></td>
				<td colspan="3"><?php echo __('plugin', 'description') ?></td>
				<td><?php echo __('application', 'action') ?></td>
			</tr>
		</thead>
		<tbody>
	<?php if(empty($plugins)) : ?>
		<tr><td colspan="6" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td></tr>
	<?php else : foreach($plugins as $plugin) : ?>
		<tr>
			<td rowspan="2" style="text-align: center">
				<input type="checkbox" name="plugins[]" id="plugin-id-<?php echo $plugin->id ?>" value="<?php echo $plugin->id ?>">
			</td>
			<td rowspan="2" class="ac-plugin-name">
				<label for="plugin-id-<?php echo $plugin->id ?>">
					<?php echo htmlspecialchars($plugin->name) ?>
				</label>
			</td>
			<td class="ac-plugin-version">
				<?php echo __('plugin', 'version', htmlspecialchars($plugin->version)) ?>
			</td>
			<td class="ac-plugin-author">
				<?php if($plugin->authorUrl) : ?>
					<a href="<?php echo htmlentities($plugin->authorUrl, ENT_QUOTES, 'UTF-8') ?>">
						<?php echo __('plugin', 'by-author', htmlspecialchars($plugin->author)) ?>
					</a>
				<?php else : ?>
					<?php echo __('plugin', 'by-author', htmlspecialchars($plugin->author)) ?>
				<?php endif; ?>
			</td>
			<td class="ac-plugin-url">
			<?php if($plugin->pluginUrl) : ?>
				<a href="<?php echo htmlentities($plugin->pluginUrl, ENT_QUOTES, 'UTF-8') ?>">
					<?php echo __('plugin', 'plugin-url') ?>
				</a>
			<?php endif; ?>
			</td>
			<td rowspan="2" class="ac-actions">
				<?php if($plugin->isEnabled) : ?>
					<button class="ac-action-plugin-deactivate"
					        type="submit"
					        name="x-deactivate"
					        value="<?php echo $plugin->id ?>">
							<?php echo __('plugin', 'deactivate') ?>
					</button>
					<?php if($frm = $plugin->settings->buildForm($page->request)) : ?>
					<a href="<?php echo $settings_action . $plugin->id ?>">
						<button class="ac-action-plugin-settings"
						        ac-plugin-id="<?php echo $plugin->id ?>"
						        type="button">
							<?php echo __('plugin', 'settings') ?>
						</button>
					</a>
					<?php
					$frm->action = $settings_action . $plugin->id;
					$frm->append('<div class="ac-form-response"></div><input type="submit" value="' . __('application', 'submit') . '" class="ac-button">');
					$settings.= "<div class=\"ac-settings\" id=\"plugin-settings{$plugin->id}\">";
					$settings.= $frm->render();
					$settings.= '</div>';
					endif;
					?>
				<?php else : ?>
					<button class="ac-action-plugin-activate"
					        type="submit"
					        name="x-activate"
					        value="<?php echo $plugin->id ?>">
						<?php echo __('plugin', 'activate') ?>
					</button>
				<?php endif; ?>
				<button class="ac-action-delete"
				        type="submit"
				        name="x-delete"
						value="<?php echo $plugin->id ?>">
					<?php echo __('plugin', 'delete') ?>
				</button>
			</td>
		</tr>
		<tr<?php echo ($i % 2 ? ' class="odd"' : '')?>>
			<td colspan="3" class="ac-plugin-description"><?php echo htmlspecialchars($plugin->description) ?></td>
		</tr>
		<?php ++$i ?>
	<?php endforeach; endif; ?>
		</tbody>
		<tfoot>
			<tr>
				<td colspan="6" style="text-align: center">
					<?php echo $paginator->render() ?>
				</td>
			</tr>
		</tfoot>
	</table>
</form>
<span class="ac-search-result"><?php echo __('application', 'search-results-' . ($plugin_count === 1 ? 's' : 'p'), number_format($plugin_count)) ?></span>
<?php echo $settings ?>