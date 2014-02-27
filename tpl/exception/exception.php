<?php
/**
 * @var $type string
 * @var $code int
 * @var $file string
 * @var $line int
 * @var $trace array
 */
?>
<table cellspacing="0">
	<tr>
		<td class="exception-title" colspan="3">
			<span><?php echo __('exception', 'error-title', $type, $code)?></span>
		</td>
	</tr>
	<tr>
		<td class="exception-message" colspan="3">
			<?php echo $message?>
		</td>
	</tr>
	<tr>
		<td class="exception-file" colspan="3">
			<?php echo $file?> <small>(<?php echo $line?>)</small>
		</td>
	</tr>
	<tr class="exception-trace-title">
		<td colspan="3">Trace</td>
	</tr>
	<tr class="exception-trace-header">
		<td><?php echo __('exception', 'function/method') ?></td>
		<td><?php echo __('exception', 'file') ?></td>
		<td><?php echo __('exception', 'line') ?></td>
	</tr>
	<?php
	if(empty($trace)) {
		?>
		<tr class="exception-trace even">
			<td colspan="3">--</td>
		</tr>
	<?php
	} else {
		$parity = array('even', 'odd');
		do {
			$parity = array_reverse($parity);
			list($method, $file, $line) = current($trace);
			echo <<<HTML
	<tr class="exception-trace {$parity[0]}">
		<td>{$method}</td>
		<td>{$file}</td>
		<td>{$line}</td>
	</tr>
HTML;
		} while(next($trace) !== false);
	}
	?>
</table>