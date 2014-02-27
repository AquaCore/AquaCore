<?php
/**
 * @var $page   \Page\Main\Account
 * @var $form   \Aqua\UI\Form
 */
$form->submit()
	->value(__('application', 'submit'))
	->attr('class', 'ac-button');
echo $form->render();
