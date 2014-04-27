<?php
/**
 * @var $items      \Aqua\Ragnarok\ItemData[]
 * @var $item_count int
 * @var $paginator  \Aqua\UI\Pagination
 * @var $search     \Aqua\UI\Search
 * @var $page       \Page\Main\Ragnarok\Server\Item
 */

use Aqua\UI\ScriptManager;

$page->theme->footer->enqueueScript(ScriptManager::script('jquery-ui'));
$page->theme->footer->enqueueScript(ScriptManager::script('aquacore.build-url'));
$page->theme->footer->enqueueScript('db-search')
	->type('text/javascript')
	->src(\Aqua\URL . '/tpl/scripts/db-search.js');

$baseUrl = $page->charmap->url(array(
	'path' => array( 'item' ),
	'action' => 'view',
	'arguments' => array( '' )
));
?>
<div class="ac-search">
	<button class="toggle" type="button"><?php echo __('application', 'advanced-search') ?></button>
	<form method="GET">
	<?php echo ac_form_path() ?>
	<div class="wrapper" <?php if(!$page->request->uri->get('search')) echo 'style="display: none"'; ?>>
		<table>
			<colgroup>
				<col style="width: 100px">
				<col style="width: 230px">
				<col style="width: 100px">
				<col style="width: 230px">
				<col style="width: 170px">
				<col>
				<col style="width: 20px">
			</colgroup>
			<tr>
				<td><label for="search-id"><?php echo $search->field('id')->getLabel() ?></label></td>
				<td><?php echo $search->field('id')->attr('id', 'search-id')->render() ?></td>
				<td><label for="search-name"><?php echo $search->field('n')->getLabel() ?></label></td>
				<td><?php echo $search->field('n')->attr('id', 'search-id')->render() ?></td>
				<td rowspan="5">
					<?php echo $search->field('job')->render() ?>
				</td>
				<td>
					<label for="search-upper-1"><?php echo $search->field('up')->option(1)->content[0] ?></label>
				</td>
				<td>
					<input type="checkbox"
					       name="up[]"
					       id="search-upper-1"
					       value="1"
				           <?php if(in_array(1, $search->field('up')->selected)) echo 'checked' ?>>
				</td>
			</tr>
			<tr>
				<td><label for="search-type"><?php echo $search->field('t')->getLabel() ?></label></td>
				<td><?php echo $search->field('t')->attr('id', 'search-type')->render() ?></td>
				<td class="item-class-title">
					<label for="search-weapon" style="display: none"><?php echo $search->field('w')->getLabel() ?></label>
					<label for="search-armor" style="display: none"><?php echo $search->field('loc')->getLabel() ?></label>
					<label for="search-ammo" style="display: none"><?php echo $search->field('ammo')->getLabel() ?></label>
				</td>
				<td class="item-class">
					<?php
					echo $search->field('w')
						->attr('id', 'search-weapon')
						->css('display', 'none')
						->render(),
						 $search->field('loc')
						->attr('id', 'search-armor')
						->css('display', 'none')
						->render(),
						 $search->field('ammo')
						->attr('id', 'search-ammo')
						->css('display', 'none')
						->render()
					?>
				</td>
				<td>
					<label for="search-upper-2"><?php echo $search->field('up')->option(2)->content[0] ?></label>
				</td>
				<td>
					<input type="checkbox"
					       name="up[]"
					       id="search-upper-2"
					       value="2"
						<?php if(in_array(2, $search->field('up')->selected)) echo 'checked' ?>>
				</td>
			</tr>
			<tr>
				<td><label><?php echo $search->field('atk')->getLabel() ?></label></td>
				<td><?php echo $search->field('atk')->render() ?></td>
				<td><label><?php echo $search->field('def')->getLabel() ?></label></td>
				<td><?php echo $search->field('def')->render() ?></td>
				<td>
					<label for="search-upper-4"><?php echo $search->field('up')->option(4)->content[0] ?></label>
				</td>
				<td>
					<input type="checkbox"
					       name="up[]"
					       id="search-upper-4"
					       value="4"
						<?php if(in_array(4, $search->field('up')->selected)) echo 'checked' ?>>
				</td>
			</tr>
			<tr>
				<td><label><?php echo $search->field('rng')->getLabel() ?></label></td>
				<td><?php echo $search->field('rng')->render() ?></td>
				<td><label><?php echo $search->field('slt')->getLabel() ?></label></td>
				<td><?php echo $search->field('slt')->render() ?></td>
				<td>
					<label for="search-upper-8"><?php echo $search->field('up')->option(8)->content[0] ?></label>
				</td>
				<td>
					<input type="checkbox"
					       name="up[]"
					       id="search-upper-8"
					       value="8"
						<?php if(in_array(8, $search->field('up')->selected)) echo 'checked' ?>>
				</td>
			</tr>
			<tr>
				<td><label><?php echo $search->field('buy')->getLabel() ?></label></td>
				<td><?php echo $search->field('buy')->render() ?></td>
				<td><label><?php echo $search->field('sell')->getLabel() ?></label></td>
				<td><?php echo $search->field('sell')->render() ?></td>
				<td colspan="2"></td>
			</tr>
			<tr>
				<td><label><?php echo $search->field('r')->getLabel() ?></label></td>
				<td><?php echo $search->field('r')->render() ?></td>
				<td><label><?php echo $search->field('c')->getLabel() ?></label></td>
				<td><?php echo $search->field('c')->render() ?></td>
				<td colspan="3">
					<button type="submit"
					        name="search"
					        value="1"><?php echo __('application', 'submit') ?></button>
					<button class="reset" type="reset"><?php echo __('application', 'reset') ?></button>
				</td>
			</tr>
		</table>
	</div>
	</form>
</div>
<table class="ac-table">
	<colgroup>
		<col style="width: 100px">
		<col style="width: 40px">
		<col>
		<col>
		<col>
		<col>
		<col>
		<col style="width: 70px">
	</colgroup>
	<thead>
		<tr>
			<td colspan="8">
				<div style="float: left">
					<form method="GET">
						<?php echo $search->limit()->render() ?>
					</form>
				</div>
				<form method="GET" style="float: right">
					<?php echo ac_form_path()?>
					<input type="text" name="id" value="<?php echo $page->request->uri->getString('id')?>" placeholder="<?php echo __('ragnarok', 'item-id') ?>" size="5">
					<input type="text" name="n" value="<?php echo $page->request->uri->getString('n')?>" placeholder="<?php echo __('ragnarok', 'name') ?>">
					<input type="submit" value="<?php echo __('application', 'search')?>">
				</form>
			</td>
		</tr>
		<tr class="alt">
			<?php echo $search->renderHeader(array(
				'id'     => __('ragnarok', 'item-id'),
			    'icon'   => '',
			    'name'   => __('ragnarok', 'name'),
			    'type'   => __('ragnarok', 'type'),
			    'weight' => __('ragnarok', 'weight'),
			    'buy'    => __('ragnarok', 'buy-price'),
			    'sell'   => __('ragnarok', 'sell-price'),
			    'custom' => __('ragnarok', 'custom'),
			)) ?>
		</tr>
	</thead>
	<tbody>
<?php if(empty($items)) : ?>
		<tr>
			<td colspan="8" class="ac-table-no-result"><?php echo __('application', 'no-search-results')?></td>
		</tr>
<?php else : foreach($items as $item) : ?>
		<tr>
			<td><?php echo $item->id?></td>
			<td><img src="<?php echo ac_item_icon($item->id)?>"></td>
			<td><a href="<?php echo $baseUrl . $item->id?>"><?php echo htmlspecialchars($item->jpName)?></a></td>
			<td><?php echo $item->type()?></td>
			<td><?php echo $item->weight?></td>
			<td><?php echo number_format($item->buyingPrice)?><small>z</small></td>
			<td><?php echo number_format($item->sellingPrice)?><small>z</small></td>
			<td><?php echo __('application', ($item->custom ? 'yes' : 'no'))?></td>
		</tr>
<?php endforeach; endif; ?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="8" style="text-align: center"><?php echo $paginator->render()?></td>
		</tr>
	</tfoot>
</table>
<span class="ac-search-result"><?php echo __('application', 'search-results-' . ($item_count === 1 ? 's' : 'p'), number_format($item_count))?></span>
