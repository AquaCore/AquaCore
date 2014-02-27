<?php
/**
 * @var $form    \Aqua\UI\Form
 * @var $page    \Page\Main\Account
 */
$form->submit()
	->value(__('registration', 'register'))
	->attr('class', 'ac-button');
echo $form->render();
