<?php
/**
 * @var $class   string
 * @var $content array
 */
?>
<div class="ac-sidebar <?php echo $class?>">
<?php foreach($content as $sidebar) :
$tabs = count($sidebar['content']);
?>
<div class="ac-sidebar-item <?php echo $sidebar['class'] . ($tabs > 1 ? ' ac-sidebar-tabs' : '')?>">
<?php
if($tabs > 1) {
	$i = 0;
	$titles = '';
	$tabs   = '';
	foreach($sidebar['content'] as &$tab) {
		$href = 'tab_' . ++$i;
		$titles.= "<li><a href=\"#{$href}\" class=\"ac-sidebar-tab\">{$tab['title']}</a></li>";
		$tabs.= "<div id=\"{$href}\" class=\"ac-sidebar-content\">{$tab['content']}</div>";
	}
echo '<ul class="ac-sidebar-title">', $titles, '</ul>', $tabs;
} else if($tabs) {
$tab = current($sidebar['content']);
if($tab['title']) echo '<div class="ac-sidebar-title">' , $tab['title'],'</div>';
echo '<div class="ac-sidebar-content">', $tab['content'],'</div>';
}
?>
</div>
<?php endforeach; ?>
</div>
