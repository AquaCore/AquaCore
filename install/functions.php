<?php
use Aqua\UI\Form;

function setup_render_form_tag($tag, $invalid = false)
{
	if($tag instanceof Form\FieldInterface) {
		$html = '';
		if($error = $tag->getError()) {
			$html.= '<tr class="ac-field-warning"><td colspan="3" class="ac_form_warning">' . $error . '</td></tr>';
		}
		$html.= '<tr class="ac-form-field">';
		if($invalid) {
			$html.= "<td class=\"ac-field-status ac-field-error\"></td>";
		} else {
			$html.= "<td class=\"ac-field-status\"></td>";
		}
		if($label = $tag->getLabel()) {
			$html.= "<td class=\"ac-form-label\">$label</td>" .
			        "<td class=\"ac-form-tag\">{$tag->render()}</td>";
		} else {
			$html.= "<td class=\"ac-form-tag\" colspan=\"2\">{$tag->render()}</td>";
		}
		$html.= '</tr>';
		if($desc = $tag->getDescription()) {
			$html.= '<tr class="ac-form-description"><td colspan="3">' . $desc . '</td></tr>';
		}
	} else {
		$html = "<tr><td colspan=\"3\">$tag</td>";
	}
	return $html;
}
