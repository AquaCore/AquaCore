<?php
/**
 * @var $error \Aqua\Log\ErrorLog
 */
$parity = array( 'even', 'odd' );
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Error</title>
	<link type="text/css"
	      href="<?php echo Aqua\URL; ?>/assets/styles/exception.css"
	      rel="stylesheet" />
</head>
<body>
<div id="body">
	<div class="title">Uncaught Exception</div>
	<div id="exceptions">
	<table style="border-spacing:0; border-collapse: collapse;">
		<colgroup>
			<col width="45%" style="width: 45%">
			<col width="45%" style="width: 45%">
			<col width="10%" style="width: 10%">
		</colgroup>
<?php do { ?>
		<tr>
			<td class="exception-title" colspan="3">
				<span><?php echo $error->type ?> - Error <?php echo $error->code ?></span>
			</td>
		</tr>
		<tr>
			<td class="exception-message" colspan="3">
				<?php echo $error->message ?>
			</td>
		</tr>
		<tr>
			<td class="exception-file" colspan="3">
				<?php echo $error->file?>
				<?php if($error->line) : ?>
					<small>(<?php echo $error->line?>)</small>
				<?php endif; ?>
			</td>
		</tr>
		<tr class="exception-trace-header">
			<td>Function/Method</td>
			<td>File</td>
			<td>Line</td>
		</tr>
		<?php if(($trace = $error->trace()) && !empty($trace)) : foreach($trace as $t) : ?>
			<tr class="exception-trace <?php echo $parity[0] ?>">
				<td title="<?php
				if(empty($t['function'])) {
					echo 'main';
				} else if(empty($t['class'])) {
					echo $t['function'];
				} else {
					echo $t['class'], $t['type'], $t['function'], '()';
				}
				?>">
				<?php
				if(empty($t['function'])) {
					echo 'main';
				} else if(empty($t['class'])) {
					echo "<span class=\"trace-function\">{$t['function']}</span>()";
				} else {
					echo "<span class=\"trace-class\">{$t['class']}</span>",
						 "<span class=\"trace-type\">{$t['type']}</span>",
						 "<span class=\"trace-function\">{$t['function']}</span>()";
				}
				?>
				</td>
				<td title="<?php echo $t['file'] ?>"><?php echo (empty($t['file']) ? '--' : $t['file']) ?></td>
				<td><?php echo (empty($t['line']) ? '--' : $t['line']) ?></td>
			</tr>
			<?php $parity = array_reverse($parity); ?>
		<?php endforeach; else : ?>
			<tr class="exception-trace even">
				<td colspan="3">--</td>
			</tr>
		<?php endif; ?>
<?php } while($error = $error->previous()); ?>
		<tfoot><tr><td colspan="3"></td></tr></tfoot>
	</table>
	</div>
	<div class="message">
		This error was most likely caused by misconfiguration,<br>
		if you believe this occurred due a bug in the application<br>
		please report this issue.
	</div>
</div>
</body>
</html>