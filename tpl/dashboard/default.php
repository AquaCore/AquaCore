<?php
/**
 * @var $class    string
 * @var $maxItems int
 * @var $content  array
 */
while(current($content)) : $items = 0; ?>
	<div class="ac-dashboard-row <?php echo $class ?>">
		<div class="ac-dashboard-row-wrapper">
			<?php
			while($item = current($content)) :
				if($items && ($item['span'] + $items) > $maxItems) {
					echo '</div></div>';
					continue 2;
				}
				$items += $item['span'];
				?>
				<div class="ac-dashboard-item <?php echo $item['class'] ?>">
					<table class="ac-table">
						<thead>
						<tr>
							<td colspan="<?php echo $item['colspan'] ?>"><?php echo $item['title'] ?></td>
						</tr>
						<?php if($item['header']) : ?>
							<tr class="alt">
								<?php foreach($item['header'] as $col) : ?>
									<td><?php echo $col ?></td>
								<?php endforeach; ?>
							</tr>
						<?php endif; ?>
						</thead>
						<tbody>
						<?php foreach($item['content'] as $row) : ?>
							<tr>
								<?php if(is_array($row)) : foreach($row as $col) : ?>
									<td><?php echo $col ?></td>
								<?php endforeach; else : ?>
									<td colspan="<?php echo $item['colspan'] ?>"><?php echo $row ?></td>
								<?php endif; ?>
							</tr>
						<?php endforeach; ?>
						</tbody>
						<tfoot>
						<tr>
							<td colspan="<?php echo $item['colspan'] ?>"><?php echo $item['footer'] ?></td>
						</tr>
						</tfoot>
					</table>
				</div>
				<?php next($content); endwhile; ?>
		</div>
	</div>
<?php endwhile; ?>
