<?php
if($form->message) {
	$form->prepend("<div class=\"ac_form_warning\">{$form->message}</div>");
}
echo $form->render('ac_setup_render_form_tag', function($content) {
	return "<table>$content</table>";
});