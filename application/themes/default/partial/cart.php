<?php
use Aqua\Ragnarok\Ragnarok;
use Aqua\Core\App;
/**
 * @var $cart array
 */
if(!isset($cart['cart']) || !isset($cart['charmap']) || !isset($cart['server']) || !App::user()->loggedIn()) {
	return;
}
$uri = $cart['server']->charmapUri($cart['charmap']->key());
$base_item_url = $uri->url(array(
	'path' => array( 'item' ),
	'action' => 'view',
	'arguments' => array( '' )
));
$base_cart_url = $uri->url(array(
	'path' => array( 'item' ),
	'action' => 'cart',
	'query' => array(
		'x' => 'remove',
		'id' => ''
	)
));
$checkout_url = $uri->url(array(
	'path' => array( 'item' ),
	'action' => 'buy'
));

$q = 0;
$t = 0;
?>
<div class="ac-cart">
	<div class="ac-cart-items ac-script">
		<div class="ac-cart-header">
			<div class="ac-cart-header-tip"></div>
			<?php echo __('ragnarok', 'Cart - %s', $cart['charmap']->name)?>
		</div>
		<ul class="ac-cart-body">
<?php if(empty($cart['cart']->items)) : ?>
		<li style="text-align: center; font-style: italic;"><?php echo __('ragnarok', 'Your cart is empty.')?></li>
<?php else : foreach($cart['cart']->items as $id => $item) : ?>
			<li>
				<img src="<?php echo ac_item_icon($id)?>">
				<div class="ac-cart-item-name">
					<a href="<?php echo $base_item_url . $id?>">
						<?php echo htmlspecialchars($item['name'])?>
					</a>
				</div>
				<span class="ac-cart-item-amount"><small>x</small><?php echo number_format($item['amount'])?></span>
				<a href="<?php echo $base_cart_url . $id?>" class="ac-cart-remove-item"></a>
			</li>
			<?php $q += $item['amount']; $t += ($item['amount'] * $item['price']); ?>
<?php endforeach;?>
			<li style="text-align: center"><b><?php echo __('application', 'Total')?>:</b> <?php echo __('application', '%s credits', number_format($t))?></li>
<?php endif; ?>
		</ul>
		<div class="ac-cart-footer">
			<a href="<?php echo $checkout_url?>">
				<?php echo __('ragnarok', 'Checkout')?>
			</a>
		</div>
	</div>
<a href="<?php echo $checkout_url?>" class="ac-cart-link"><div class="ac-cart-icon"></div><div class="ac-cart-count"><?php echo $q?></div></a>
</div>
