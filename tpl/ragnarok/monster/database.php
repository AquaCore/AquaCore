<?php
/**
 * @var $mobs       \Aqua\Ragnarok\Mob[]
 * @var $mob_count  int
 * @var $paginator  \Aqua\UI\Pagination
 * @var $search     \Aqua\UI\Search
 * @var $page       \Page\Main\Ragnarok\Server\Item
 */

use Aqua\UI\ScriptManager;

$page->theme->footer->enqueueScript(ScriptManager::script('aquacore.build-url'));
$page->theme->footer->enqueueScript('cardbmp')
	->type('text/javascript')
	->src(\Aqua\URL  . '/tpl/scripts/cardbmp.js');
$page->theme->footer->enqueueScript('db-search')
	->type('text/javascript')
	->src(\Aqua\URL . '/tpl/scripts/db-search.js');
$baseCardUrl = $page->charmap->url(array(
	'path' => array( 'item' ),
	'action' => 'view',
	'arguments' => array( '' )
));
$baseMobUrl = $page->charmap->url(array(
	'path' => array( 'mob' ),
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
				<col style="width: 90px">
				<col style="width: 230px">
				<col style="width: 80px">
				<col style="width: 230px">
				<col>
				<col style="width: 55px">
				<col>
				<col style="width: 55px">
			</colgroup>
			<tr>
				<td><label for="search-id"><?php echo $search->field('id')->getLabel() ?></label></td>
				<td><?php echo $search->field('id')->attr('id', 'search-id')->render() ?></td>
				<td><label for="search-name"><?php echo $search->field('n')->getLabel() ?></label></td>
				<td><?php echo $search->field('n')->attr('id', 'search-id')->render() ?></td>
				<!--- Looter -->
				<td><label for="search-mode-1"><?php echo $search->field('m1')->getLabel() ?></label></td>
				<td><?php echo $search->field('m1')->attr('id', 'search-mode-1')->render() ?></td>
				<!--- Aggressive -->
				<td><label for="search-mode-2"><?php echo $search->field('m2')->getLabel() ?></label></td>
				<td><?php echo $search->field('m2')->attr('id', 'search-mode-2')->render() ?></td>
			</tr>
			<tr>
				<td><label for="search-race"><?php echo $search->field('race')->getLabel() ?></label></td>
				<td><?php echo $search->field('race')->attr('id', 'search-race')->render() ?></td>
				<td><label for="search-size"><?php echo $search->field('size')->getLabel() ?></label></td>
				<td><?php echo $search->field('size')->attr('id', 'search-sizeelement')->render() ?></td>
				<!--- Assist -->
				<td><label for="search-mode-3"><?php echo $search->field('m3')->getLabel() ?></label></td>
				<td><?php echo $search->field('m3')->attr('id', 'search-mode-3')->render() ?></td>
				<!--- Cast Sensor -->
				<td><label for="search-mode-4"><?php echo $search->field('m4')->getLabel() ?></label></td>
				<td><?php echo $search->field('m4')->attr('id', 'search-mode-4')->render() ?></td>
			</tr>
			<tr>
				<td><label for="search-element"><?php echo $search->field('el')->getLabel() ?></label></td>
				<td><?php echo $search->field('el')->attr('id', 'search-element')->render() ?></td>
				<td><label><?php echo $search->field('lv')->getLabel() ?></label></td>
				<td><?php echo $search->field('lv')->render() ?></td>
				<!--- Boss -->
				<td><label for="search-mode-5"><?php echo $search->field('m5')->getLabel() ?></label></td>
				<td><?php echo $search->field('m5')->attr('id', 'search-mode-5')->render() ?></td>
				<!--- Can Attack -->
				<td><label for="search-mode-7"><?php echo $search->field('m7')->getLabel() ?></label></td>
				<td><?php echo $search->field('m7')->attr('id', 'search-mode-7')->render() ?></td>
			</tr>
			<tr>
				<td><label><?php echo $search->field('bxp')->getLabel() ?></label></td>
				<td><?php echo $search->field('bxp')->render() ?></td>
				<td><label><?php echo $search->field('jxp')->getLabel() ?></label></td>
				<td><?php echo $search->field('jxp')->render() ?></td>
				<!--- Plant -->
				<td><label for="search-mode-6"><?php echo $search->field('m6')->getLabel() ?></label></td>
				<td><?php echo $search->field('m6')->attr('id', 'search-mode-6')->render() ?></td>
				<!--- Change Chase -->
				<td><label for="search-mode-9"><?php echo $search->field('m9')->getLabel() ?></label></td>
				<td><?php echo $search->field('m9')->attr('id', 'search-mode-9')->render() ?></td>
			</tr>
			<tr>
				<td><label><?php echo $search->field('ar')->getLabel() ?></label></td>
				<td><?php echo $search->field('ar')->render() ?></td>
				<td><label><?php echo $search->field('sr')->getLabel() ?></label></td>
				<td><?php echo $search->field('sr')->render() ?></td>
				<!--- Detector -->
				<td><label for="search-mode-8"><?php echo $search->field('m8')->getLabel() ?></label></td>
				<td><?php echo $search->field('m8')->attr('id', 'search-mode-8')->render() ?></td>
				<!--- Change Target -->
				<td><label for="search-mode-11"><?php echo $search->field('m11')->getLabel() ?></label></td>
				<td><?php echo $search->field('m11')->attr('id', 'search-mode-11')->render() ?></td>
			</tr>
			<tr>
				<td colspan="4">
				</td>
				<!--- Angry -->
				<td><label for="search-mode-10"><?php echo $search->field('m10')->getLabel() ?></label></td>
				<td><?php echo $search->field('m10')->attr('id', 'search-mode-10')->render() ?></td>
				<!--- Target Weak -->
				<td><label for="search-mode-12"><?php echo $search->field('m12')->getLabel() ?></label></td>
				<td><?php echo $search->field('m12')->attr('id', 'search-mode-12')->render() ?></td>
			</tr>
			<tr>
				<td colspan="8" style="text-align: right">
					<button type="submit"
					        name="search"
					        value="1"><?php echo __('application', 'submit') ?></button>
					<button class="reset" type="reset"><?php echo __('application', 'reset') ?></button>
				</td>
			</tr>
		</table>
	</div>
</div>
<table class="ac-table ac-mob-database">
	<colgroup>
		<col>
		<col>
		<col>
		<col>
		<col>
		<col>
		<col>
		<col>
		<col style="width: 40px">
		<col style="width: 75px">
	</colgroup>
	<thead>
		<tr>
			<td colspan="10">
				<div style="float: left">
					<form method="GET">
						<?php echo $search->limit()->render() ?>
					</form>
				</div>
				<form method="GET" style="float: right">
					<?php echo ac_form_path()?>
					<input type="text" name="id" value="<?php echo htmlspecialchars($page->request->uri->getString('id'))?>" placeholder="<?php echo __('ragnarok', 'mob-id') ?>" size="8">
					<input type="text" name="n" value="<?php echo htmlspecialchars($page->request->uri->getString('n'))?>" placeholder="<?php echo __('ragnarok', 'name') ?>">
					<input type="submit" value="<?php echo __('application', 'search')?>">
				</form>
			</td>
		</tr>
		<tr class="alt">
			<?php echo $search->renderHeader(array(
					'id'      => __('ragnarok', 'mob-id'),
					'name'    => __('ragnarok', 'name'),
					'lvl'     => __('ragnarok', 'level'),
					'bxp'     => __('ragnarok', 'base-exp'),
					'jxp'     => __('ragnarok', 'job-exp'),
					'size'    => __('ragnarok', 'size'),
					'race'    => __('ragnarok', 'race'),
					'element' => __('ragnarok', 'element'),
					'card'    => __('ragnarok', 'card'),
					'custom'  => __('ragnarok', 'custom'),
				)) ?>
		</tr>
	</thead>
	<tbody>
<?php if(empty($mobs)) : ?>
		<tr>
			<td colspan="10" class="ac-table-no-result"><?php echo __('application', 'no-search-results')?></td>
		</tr>
<?php else : foreach($mobs as $mob) : ?>
		<tr>
			<td><?php echo $mob->id?></td>
			<td><a href="<?php echo $baseMobUrl . $mob->id?>"><?php echo htmlspecialchars($mob->iName)?></a></td>
			<td><?php echo $mob->level?></td>
			<td><?php echo number_format($mob->baseExp)?></td>
			<td><?php echo number_format($mob->jobExp)?></td>
			<td><?php echo $mob->size()?></td>
			<td><?php echo $mob->race()?></td>
			<td><?php echo $mob->element() . ' ' . $mob->elementLevel()?></td>
			<?php if($mob->cardId) { ?>
				<td class="ac-card-slot" ac-ro-card="<?php echo ac_item_cardbmp($mob->cardId)?>" title="">
					<a href="<?php echo $baseCardUrl . $mob->cardId?>"></a>
				</td>
			<?php } else { ?>
				<td class="ac-card-slot ac-slot-empty"></td>
			<?php } ?>
			<td><?php echo __('application', ($mob->custom ? 'yes' : 'no'))?></td>
		</tr>
<?php endforeach; endif; ?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="10" style="text-align: center">
				<?php echo $paginator->render()?>
			</td>
		</tr>
	</tfoot>
</table>
<span class="ac-search-result"><?php echo __('application', 'search-results-' . ($mob_count === 1 ? 's' : 'p'), number_format($mob_count))?></span>
