<?php
use Aqua\Core\App;

/**
 * @var \Aqua\Log\ErrorLog $error
 * @var \Page\Admin\Log    $page
 */

$plain_text_url = ac_build_url(array(
		'path'      => array( 'log' ),
		'action'    => 'viewerror',
		'arguments' => array( $error->id, 'text' )
	));
$i = 0;
?>
<table class="ac-table error-table">
	<thead>
		<tr class="ac-table-header alt">
			<td colspan="3"><?php echo __('error', 'error-title', $error->id, htmlspecialchars($error->type), htmlspecialchars($error->code)) ?></td>
		</tr>
	</thead>
	<tbody>
	<?php do { ?>
	<?php if($i) : ?>
	<tr class="ac-table-header alt">
		<td colspan="3"><?php echo __('error', 'error-title', $error->id, htmlspecialchars($error->type), htmlspecialchars($error->code)) ?></td>
	</tr>
	<?php endif; ?>
	<?php if($error->message) : ?>
		<tr class="ac-error-message">
			<td colspan="3">
				<?php echo $error->message ?>
			</td>
		</tr>
	<?php endif; ?>
	<tr>
		<td><b><?php echo __('error', 'file') ?></b></td>
		<td colspan="2">
			<?php echo ($error->file ? htmlspecialchars($error->file) : '--') ?>
			<?php if($error->line) : ?>
				<span class="ac-error-line">(<?php echo __('error', 'line-number', $error->line) ?>)</span>
			<?php endif; ?>
		</td>
	</tr>
	<?php if($i === 0) : ?>
	<tr>
		<td><b><?php echo __('error', 'date') ?></b></td>
		<td colspan="2"><?php echo $error->date(App::settings()->get('datetime_format')) ?></td>
	</tr>
	<tr>
		<td><b><?php echo __('error', 'url') ?></b></td>
		<td colspan="2"><a href="<?php echo $error->url ?>"><?php echo htmlspecialchars($error->url) ?></a></td>
	</tr>
	<tr>
		<td><b><?php echo __('error', 'ip-address') ?></b></td>
		<td colspan="2"><?php echo htmlspecialchars($error->ipAddress) ?></td>
	</tr>
	<?php if($user = $error->user()) : ?>
	<tr>
		<td><b><?php echo __('error', 'user') ?></b></td>
		<td colspan="2">
			<a href="<?php echo ac_build_url(array(
					'path' => array( 'user' ),
					'action' => 'view',
					'arguments' => array( $user->id )
				)) ?>">
			<?php echo $error->user()->display() ?>
			</a>
		</td>
	</tr>
	<?php endif; ?>
	<?php endif; ?>
	<tr class="ac-trace-header ac-table-header">
		<td><?php echo __('error', 'function') ?></td>
		<td><?php echo __('error', 'file') ?></td>
		<td><?php echo __('error', 'line') ?></td>
	</tr>
	<?php if(($trace = $error->trace()) && !empty($trace)) : foreach($trace as $t) : ?>
	<tr>
		<td>
		<?php
		if(empty($t['function'])) {
			echo 'main';
		} else if(empty($t['class'])) {
			echo '<span class="ac-trace-function">', htmlspecialchars($t['function']), '</span>()';
		} else {
			echo '<span class="ac-trace-class">', htmlspecialchars($t['class']), '</span>',
				 '<span class="ac-trace-type">', htmlspecialchars($t['type']), '</span>',
				 '<span class="ac-trace-function">', htmlspecialchars($t['function']), '</span>()';
		}
		?>
		</td>
		<td><?php echo (empty($t['file']) ? '--' : htmlspecialchars($t['file'])) ?></td>
		<td><?php echo (empty($t['line']) ? '--' : $t['line']) ?></td>
	</tr>
	<?php endforeach; else : ?>
	<tr><td colspan="3" style="text-align: center">--</td></tr>
	<?php endif; ?>
	<?php ++$i ?>
<?php } while($error = $error->previous()); ?>
	</tbody>
	<tfoot>
	<tr>
		<td colspan="3">
			<a href="<?php echo $plain_text_url ?>">
				<button type="button" class="ac-button">
					<?php echo __('error', 'view-plain-text') ?>
				</button>
			</a>
		</td>
	</tr>
	</tfoot>
</table>
