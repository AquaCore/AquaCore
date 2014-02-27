<?php
use Aqua\Core\App;
/**
 * @var $pages      \Aqua\Content\Adapter\Page[]
 * @var $page_count int
 * @var $paginator  \Aqua\UI\Pagination
 * @var $page       \Page\Admin\Page
 */
$datetime_format = App::settings()->get('datetime_format');
?>
<form method="POST">
<table class="ac-table">
	<colgroup>
		<col style="width> 30px;"/>
		<col/>
		<col/>
		<col/>
		<col/>
		<col/>
		<col/>
		<col/>
		<col/>
		<col/>
	</colgroup>
	<thead>
		<tr>
			<td colspan="10" style="text-align: right">
				<select name="action">
					<option name="delete"><?php echo __('page', 'delete') ?></option>
				</select>
				<input type="submit" value="<?php echo __('application', 'apply') ?>">
			</td>
		</tr>
		<tr class="alt">
			<td><input type="checkbox" ac-checkbox-toggle="pages[]"></td>
			<td><?php echo __('content', 'id') ?></td>
			<td><?php echo __('content', 'title') ?></td>
			<td><?php echo __('content', 'slug') ?></td>
			<td><?php echo __('content', 'parent') ?></td>
			<td><?php echo __('content', 'author') ?></td>
			<td><?php echo __('content', 'editor') ?></td>
			<td><?php echo __('content', 'status') ?></td>
			<td><?php echo __('content', 'publish-date') ?></td>
			<td><?php echo __('content', 'edit-date') ?></td>
		</tr>
	</thead>
	<tbody>
	<?php if(empty($pages)) : ?>
		<tr><td colspan="10" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td></tr>
	<?php else : foreach($pages as $p) : ?>
		<tr>
			<td><input type="checkbox" name="pages[]" value="<?php echo $p->uid ?>"></td>
			<td><?php echo $p->uid ?></td>
			<td><a href="<?php echo ac_build_url(array(
					'path'      => array( 'page' ),
					'action'    => 'edit',
				    'arguments' => array( $p->uid )
				)) ?>"><?php echo htmlspecialchars($p->title) ?></a></td>
			<td><a href="<?php echo ac_build_url(array(
					'path' => array( 'page', $p->slug ),
			        'base_dir' => \Aqua\DIR
				)) ?>"><?php echo htmlspecialchars($p->slug) ?></a></td>
			<?php if($parent = $p->parent()) : ?>
				<td><a href="<?php echo ac_build_url(array(
					'path'      => array( 'page' ),
					'action'    => 'edit',
					'arguments' => array( $parent->uid )
				)) ?>"><?php echo htmlspecialchars($parent->title) ?></a></td>
			<?php else : ?>
				<td style="text-align: center">--</td>
			<?php endif; ?>
			<td><a href="<?php echo ac_build_url(array(
					'path'      => array( 'user' ),
					'action'    => 'view',
					'arguments' => array( $p->authorId )
				)) ?>"><?php echo $p->author()->display() ?></a></td>
			<?php if($p->lastEditorId) : ?>
				<td><a href="<?php echo ac_build_url(array(
						'path'      => array( 'user' ),
						'action'    => 'view',
						'arguments' => array( $p->lastEditorId )
					)) ?>"><?php echo $p->lastEditor()->display() ?></a></td>
			<?php else : ?>
				<td style="text-align: center">--</td>
			<?php endif; ?>
			<td><?php echo __('page-status', $p->status) ?></td>
			<td><?php echo $p->publishDate($datetime_format) ?></td>
			<?php if($p->editDate) : ?>
				<td><?php echo $p->editDate($datetime_format) ?></td>
			<?php else : ?>
				<td style="text-align: center">--</td>
			<?php endif; ?>
		</tr>
	<?php endforeach; endif; ?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="10" style="text-align: center">
				<div style="position: relative">
					<?php echo $paginator->render() ?>
					<div style="position: absolute; top: 3px; right: 10px">
						<a href="<?php echo ac_build_url(array(
								'path' => array( 'page' ),
								'action' => 'new'
							)) ?>"><button type="button"><?php echo __('page', 'new-page') ?></button></a>
					</div>
				</div>
			</td>
		</tr>
	</tfoot>
</table>
</form>
<span class="ac-search-result"><?php echo __('application', 'search-results-' . ($page_count === 1 ? 's' : 'p'), number_format($page_count)) ?></span>
