<?php
/**
 * @var $page   \Page\Main\Ragnarok\Account
 * @var $form   \Aqua\UI\Form
 */
if($form->message) {
	$form->prepend("<div class=\"ac_form_warning\">{$form->message}</div>");
}
$form->submit()
	->value(__('application', 'submit'))
	->attr('class', 'ac-button');
echo $form->render();
