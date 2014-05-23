<?php

if(!function_exists('boolval')) {
	/** Checks a variable to see if it should be considered a boolean true or false.
	 *     Also takes into account some text-based representations of true of false,
	 *     such as 'false','N','yes','on','off', etc.
	 * @author Samuel Levy <sam+nospam@samuellevy.com>
	 * @param mixed $in The variable to check
	 * @param bool $strict If set to false, consider everything that is not false to
	 *                     be true.
	 * @return bool The boolean equivalent or null (if strict, and no exact equivalent)
	 */
	function boolval($in, $strict=false) {
		$out = null;
		$in = (is_string($in)?strtolower($in):$in);
		// if not strict, we only have to check if something is false
		if (in_array($in,array('false','no', 'n','0','off',false,0), true) || !$in) {
			$out = false;
		} else if ($strict) {
			// if strict, check the equivalent true values
			if (in_array($in,array('true','yes','y','1','on',true,1), true)) {
				$out = true;
			}
		} else {
			// not strict? let the regular php bool check figure it out (will
			//     largely default to true)
			$out = ($in?true:false);
		}
		return $out;
	}
}
