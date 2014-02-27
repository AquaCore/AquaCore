<?php
/**
 * @var $form \Aqua\UI\Form
 * @var $page \Page\Main\Account
 */
$form->submit()
	->value(__('login', 'login'))
	->attr('class', 'ac-button');
echo $form->render();
