<?php

/**
 * @var $credits  int
 * @var $amount   float
 * @var $page     \Page\Main\Donate
 */
use Aqua\Core\App;

$currency = strtoupper(App::settings()->get('donation')->get('currency', ''));
$user = App::user();
$user->session->set('ac_donation', true);
?>
<?php if($user->loggedIn()) : ?>
	<div class="ac-user-credits"><?php echo __('donation',
	                                           'credit-points',
	                                           number_format($user->account->credits)) ?></div>
<?php else : ?>
	<div class="ac-warning"><?php echo __('donation', 'login-warning') ?></div>
<?php endif ?>
<?php echo __('donation', 'donation-message') ?>
<table class="ac-table" style="margin-top: 10px;">
	<thead><tr><td colspan="2"></td></tr></thead>
	<tbody>
	<tr>
		<td style="width: 50%"><?php echo __('donation', 'exchange-rate') ?></td>
		<td>
			<?php echo round(App::settings()->get('donation')->get('exchange_rate', 1), 2) ?>
			<small><?php echo $currency ?></small>
			=
			<?php echo __('donation', 'credit-points', 1) ?>
		</td>
	</tr>
	<tr>
		<td><?php echo __('donation', 'min-donation') ?></td>
		<td>
			<?php echo round(App::settings()->get('donation')->get('min_donation', 1), 2) ?>
			<small><?php echo $currency ?></small>
		</td>
	</tr>
	</tbody>
	<tfoot>
	<tr>
		<td colspan="2">
			<form method="POST">
				<div style="float: right; margin-left: 25px;">
					<form method="POST" action="<?php echo(App::settings()->get('donation')->get('pp_sandbox', false) ?
						'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr') ?>">
						<input type="hidden" name="cmd" value="_donations">
						<input type="hidden" name="notify_url" value="<?php echo ac_build_url(array(
								'protocol' => ( App::settings()->get('ssl', 0) > 0 ? 'https://' : 'http://'),
								'domain'   => \Aqua\DOMAIN,
								'base_dir' => App::settings()->get('base_dir') . '/paypal.php'
							)) ?>">
						<input type="hidden" name="return" value="<?php echo ac_build_url(array(
								'path' => array( 'donation' ),
								'action' => 'thankyou'
							)) ?>"/>
						<input type="hidden" name="custom" value="<?php echo($user->loggedIn() ? $user->account->id : 0) ?>"/>
						<input type="hidden" name="business"
						       value="<?php echo App::settings()->get('donation')->get('pp_business_url', '') ?>">
						<input type="hidden" name="item_name"
						       value="<?php echo __('donation', 'pp-item-name', number_format($credits)) ?>">
						<input type="hidden" name="currency_code" value="<?php echo $currency ?>">
						<input type="hidden" name="amount" value="<?php echo $credits ?>">
						<input type="hidden" name="no_shipping" value="1">
						<input type="hidden" name="no_note" value="1">
						<input type="hidden" name="tax" value="0">
						<input type="hidden" name="bn" value="PP-DonationsBF">
						<input style="border: none; padding: 0" type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_LG.gif" name="submit"/>
					</form>
				</div>
				<div style="float: right">
					<b><?php echo number_format($amount, 2) ?></b> <?php echo $currency ?>
					<small>(<?php echo __('donation', 'credit-points', number_format($credits)) ?>)</small>
				</div>
			</form>
		</td>
	</tr>
	</tfoot>
</table>
