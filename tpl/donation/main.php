<?php
use Aqua\Core\App;
/**
 * @var $page    \Page\Main\Donate
 */
$currency = (string)App::settings()->get('donation')->get('currency', '');
$user = App::user();
?>
<?php if($user->loggedIn()) : ?>
	<div class="ac-user-credits"><?php echo __('donation', 'credit-points', number_format($user->account->credits))?></div>
<?php else : ?>
	<div class="ac-warning"><?php echo __('donation', 'login-warning')?></div>
<?php endif ?>
<?php echo __('donation', 'donation-message')?>
<table class="ac-table" style="margin-top: 10px;">
	<thead><tr><td colspan="2"></td></tr></thead>
	<tbody>
	<tr>
		<td style="width: 50%"><?php echo __('donation', 'exchange-rate')?></td>
		<td>
			<?php echo round(App::settings()->get('donation')->get('exchange_rate'), 2)?> <small><?php echo $currency?></small>
			=
			<?php echo __('donation', 'credit-points', 1)?>
		</td>
	</tr>
	<tr>
		<td><?php echo __('donation', 'min-donation')?></td>
		<td>
			<?php echo round(App::settings()->get('donation')->get('min_donation'), 2)?> <small><?php echo $currency?></small>
		</td>
	</tr>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="2">
				<form method="POST">
				<div style="float: right; margin-left: 25px;">
					<input type="submit" value="<?php echo __('donation', 'confirm-amount')?>">
				</div>
				<div style="float: right">
					<input type="text" id="donation-amount" name="amount" value="0.00" size="4"><small><?php echo $currency?></small>
					<?php echo __('donation', 'credit-points', '<span class="credits ac-script" style="margin-left: 20px">0</span>') ?>
				</div>
				</form>
			</td>
		</tr>
	</tfoot>
</table>
<script>
	$('#donation-amount').bind('keyup', function(e) {
		var credits, amount = parseFloat($(this).val());
		if(isNaN(amount) || amount < <?php echo (float)App::settings()->get('donation')->get('min_donation')?>) {
			credits = 0;
		} else {
		credits = Math.floor(amount / <?php echo App::settings()->get('donation')->get('credits_price', 1)?>);
		}
		$(this).parent().find('.credits').html(credits.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","));
	});
</script>

