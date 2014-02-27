<?php
use Aqua\UI\Form;
/**
 * @var $form    \Aqua\UI\Form
 * @var $page    \Page\Main\Ragnarok
 */
$form->input('submit')->value(__('application', 'register'))->attr('class', 'ac-button');
echo $form->render();
