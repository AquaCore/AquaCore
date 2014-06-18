<?php
use Aqua\Core\App;
/**
 * @var $cart \Aqua\Ragnarok\Cart
 */
if(!isset($cart) || !App::user()->loggedIn()) {
	return;
}

$base_item_url = $cart->charmap()->url(array(
	'path'      => array( 'item' ),
	'action'    => 'view',
	'arguments' => array( '' )
));
$base_cart_url = $cart->charmap()->url(array(
	'path'   => array( 'item' ),
	'action' => 'cart',
	'query'  => array(
		'x'  => 'remove',
		'r'  => base64_encode(App::request()->uri->url()),
		'id' => ''
	)
));
$checkout_url = $cart->charmap()->url(array(
	'path'   => array( 'item' ),
	'action' => 'buy'
));
?>
<div class="ac-cart">
	<div class="ac-cart-items ac-script">
		<div class="ac-cart-header">
			<div class="ac-cart-header-tip"></div>
			<?php echo __('ragnarok', 'cart-name', htmlspecialchars($cart->charmap()->name))?>
		</div>
		<ul class="ac-cart-body">
<?php if(empty($cart->items)) : ?>
		<li style="text-align: center; font-style: italic;"><?php echo __('ragnarok', 'cart-empty')?></li>
<?php else : foreach($cart->items as $id => $item) : ?>
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
<?php endforeach;?>
			<li style="text-align: center"><b><?php echo __('application', 'total')?>:</b> <?php echo __('donation', 'credit-points', number_format($cart->total))?></li>
<?php endif; ?>
		</ul>
		<div class="ac-cart-footer">
			<a href="<?php echo $checkout_url?>">
				<?php echo __('ragnarok', 'checkout')?>
			</a>
		</div>
	</div>
<a href="<?php echo $checkout_url?>" class="ac-cart-link"><div class="ac-cart-icon"></div><div class="ac-cart-count"><?php echo number_format($cart->itemCount)?></div></a>
</div>
