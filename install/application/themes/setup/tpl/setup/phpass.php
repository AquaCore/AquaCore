<?php
$page->theme->footer->enqueueScript('jquery')
                    ->type('text/javascript')
                    ->src('//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js');
$page->theme->footer->enqueueScript('theme.cache')
                    ->type('text/javascript')
                    ->append('
(function($) {
	var tbl = document.getElementById("hash-settings");

	$(".optional", tbl).hide();
	$(".adapter-field").on("change", function() {
		$(".optional", tbl).hide();
		$(".optional." + $("select", this).val()).show();
	}).change();
})(jQuery);
');
$render = function(\Aqua\UI\Form\FieldInterface $field, array $classes) {
	$html = '';
	$classes = implode(' ', $classes);
	if($err = $field->getError()) {
		$html.= '<tr class="ac-field-error"><td class="ac_form_warning">' . $err . '</td></tr>';
	}
	$html.= '<tr class="ac-form-field ' . $classes . '">';
	if($field->getLabel()) {
		$html.= '<td class="ac-form-label">' . $field->getLabel() . '</td>';
		$html.= '<td class="ac-form-tag">' . $field->render() . '</td>';
	} else {
		$html.= '<td class="ac-form-tag" colspan="2">' . $field->render() . '</td>';
	}
	$html.= '</tr>';
	if($desc = $field->getDescription()) {
		$html.= '<tr class="ac-form-description ' . $classes . '"><td colspan="2">' . $desc . '</td></tr>';
	}
	return $html;
}
?>
<table id="hash-settings" style="table-layout: fixed">
	<?php if($form->message) : ?>
		<tr>
			<td class="ac_form_warning" colspan="2"><?php echo $form->message ?></td>
		</tr>
	<?php endif; ?>
	<?php
	echo $render($form->field('adapter'), array( 'adapter-field' ));
	if($field = $form->field('identifier')) {
		echo $render($field, array( 'optional', 'bcrypt' ));
	}
	echo
	$render($form->field('digest'), array( 'optional', 'pbkdf2' )),
	$render($form->field('bcrypt-iteration'), array( 'optional', 'bcrypt' )),
	$render($form->field('pbkdf2-iteration'), array( 'optional', 'pbkdf2' )),
	$render($form->field('portable-iteration'), array( 'optional', 'portable' )),
	$render($form->field('sha1-iteration'), array( 'optional', 'sha1' )),
	$render($form->field('sha256-iteration'), array( 'optional', 'sha256' )),
	$render($form->field('sha512-iteration'), array( 'optional', 'sha512' ));
	?>
</table>
