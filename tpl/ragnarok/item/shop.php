<?php
use Aqua\Core\App;
/**
 * @var $items      \Aqua\Ragnarok\ItemData[]
 * @var $item_count int
 * @var $categories \Aqua\Ragnarok\ShopCategory[]
 * @var $paginator  \Aqua\UI\Pagination
 * @var $page       \Page\Main\Ragnarok\Server\Item
 */
$i = 0;
$base_cart_url = $page->charmap->url(array(
	'path' => array( 'item' ),
	'action' => 'cart',
	'query' => array(
		'x' => 'add',
		'a' => 1,
		'r' => base64_encode(App::request()->uri->url()),
		'id' => ''
	)
));
$base_item_url = $page->charmap->url(array(
	'path' => array( 'item' ),
	'action' => 'view',
	'arguments' => array( '' )
));
$description = null;
?>
<?php if(App::user()->loggedIn()) : ?>
<div class="ac-user-credits"><?php echo __('donation', 'credit-points', number_format(App::user()->account->credits))?></div>
<?php endif; ?>
<div class="ac-cash-shop-categories">
<?php foreach($categories as $id => $category) : ?>
<?php if(strcasecmp($page->request->uri->arg(0, null), $category->slug) === 0) : $description = $category->description; ?>
		<a href="<?php echo $page->charmap->url(array(
			'path'   => array( 'item' ),
			'action' => 'shop'
		))?>">
			<button class="active"><?php echo htmlspecialchars($category->name) ?></button>
		</a>
<?php else : ?>
		<a href="<?php echo $category->url()?>">
			<button><?php echo htmlspecialchars($category->name)?></button>
		</a>
<?php endif; ?>
<?php endforeach; ?>
</div>
<?php if($description) : ?>
<div style="white-space: pre-wrap; text-align: center">
<?php echo $description ?>
</div>
<?php endif; if(empty($items)) : ?>
	<div style="text-align: center"><?php echo __('application', 'no-search-results')?></div>
	<?php return; ?>
<?php else : ?>
	<table class="ac-cash-shop-table">
<?php do{ ?>
		<tr>
<?php for($j = 0; $j < 4; ++$i, ++$j) : ?>
			<td class="ac-cash-shop-item">
<?php if(isset($items[$i])) : ?>
				<table class="ac-table">
					<thead>
						<tr>
							<td colspan="2" class="ac-cash-shop-item-name">
								<img src="<?php echo ac_item_icon($items[$i]->id)?>">
								<a href="<?php echo $base_item_url . $items[$i]->id?>">
									<?php echo htmlspecialchars($items[$i]->jpName)?>
								</a>
							</td>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td colspan="2" style="font-size: .85em">
								<a href="<?php echo $page->charmap->shopCategory($items[$i]->shopCategoryId)->url()?>">
									<?php echo htmlspecialchars($page->charmap->shopCategory($items[$i]->shopCategoryId)->name)?>
								</a>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="ac-cash-shop-item-image">
								<img src="<?php echo ac_item_collection($items[$i]->id)?>">
							</td>
						</tr>
					</tbody>
					<tfoot>
						<tr>
							<td colspan="2" class="ac-cash-shop-item-button">
								<?php if(App::user()->loggedIn()) : ?>
									<a href="<?php echo $base_cart_url . $items[$i]->id?>">
										<button class="ac-button"><?php echo __('ragnarok', 'add-to-cart')?> <small>(<?php echo __('donation', 'credit-points', number_format($items[$i]->shopPrice))?>)</small></button>
									</a>
								<?php else : ?>
									<button class="ac-button" disabled><?php echo __('ragnarok', 'add-to-cart')?> <small>(<?php echo __('donation', 'credit-points', number_format($items[$i]->shopPrice))?>)</small></button>
								<?php endif; ?>
							</td>
						</tr>
					</tfoot>
				</table>
<?php endif; ?>
			</td>
<?php endfor; ?>
		</tr>
<?php }while(isset($items[$i])); ?>
	</table>
<?php endif; ?>

<div style="text-align: center"><?php echo $paginator->render()?></div>
<span class="ac-search-result"><?php echo __('ragnarok', 'x-items', number_format($item_count))?></span>
