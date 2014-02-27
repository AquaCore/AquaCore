<?php
/**
 * @var $role \Aqua\User\Role
 * @var $form \Aqua\UI\Form
 * @var $page \Page\Admin\Role
 */
$form->field('color')->type('text')->placeholder('#FFFFFF');
$form->field('background')->type('text')->placeholder('#FFFFFF');
$form->field('permission')->setClass('ac-permission-list');
$form->submit()->attr('class', 'ac-button');
echo $form->render();