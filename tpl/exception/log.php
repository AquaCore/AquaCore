<?php
/**
 * @var $error \Aqua\Log\ErrorLog
 */
?>
Date:			<?php echo $error->date('%b %d, %Y %X') . "\r\n" ?>
URL:			<?php echo $error->url . "\r\n" ?>
IP Address:		<?php echo $error->ipAddress . "\r\n" ?>
<?php do { ?>
--------------------------------------------------------------------
                            Exception
-------------------------------------------------------------------
File:	<?php echo $error->file . "\r\n" ?>
Line:	<?php echo $error->line . "\r\n" ?>
Type:	<?php echo $error->type . "\r\n" ?>
Code:	<?php echo $error->code . "\r\n" ?>
<?php if($mes = $error->message) : ?>
- Message ---------------------------------------------------------
<?php
$len = strlen($mes);
if($len <= 100) {
	echo $mes;
} else {
	$i = 0;
	while(($i + 64) < $len) {
		$chk = substr($mes, $i, 64);
		if(($pos = strrpos($chk, ' ')) === false) {
			echo substr($chk, 0, 63) . "-\r\n";
			$i += 63;
		} else {
			echo substr($chk, 0, $pos) . "\r\n";
			$i += $pos + 1;
		}
	}
	echo substr($mes, $i), "\r\n";
}
?>
<?php endif; ?>
<?php if($trace = $error->trace()) : ?>

- Trace ------------------------------------------------------------
<?php
$i = 0;
$count = count($trace);
foreach($trace as $t) {
	echo "* ";
	if(empty($t['function'])) {
		echo 'main';
	} else if(empty($t['class'])) {
		echo "{$t['function']}()";
	} else {
		$t['class'] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $t['class']);
		echo "{$t['class']}{$t['type']}{$t['function']}()";
	}
	if(!empty($t['file'])) {
		echo "\t {$t['file']}";
		if(!empty($t['line'])) {
			echo ":{$t['line']}";
		}
	}
	if($i < $count) echo "\r\n";
}
?>
<?php endif; ?>
<?php } while($error = $error->previous()); ?>
