<!DOCTYPE html>
<!--[if lt IE 7 ]>
<html class="ie6 ie">
<![endif]-->
<!--[if IE 7 ]>
<html class="ie ie7">
<![endif]-->
<!--[if IE 8 ]>
<html class="ie ie8">
<![endif]-->
<!--[if (gte IE 9)|!(IE)]><!-->
<html>
<!--<![endif]-->
<head>
	<?php echo $head->render()?>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link type="text/css" href="<?php echo $this->url?>/stylesheets/error.css" rel="stylesheet" />
</head>
<body class="<?php echo $body_class?>">
<div id="main">
	<?php echo $content?>
</div>
<?php echo $footer->render()?>
</body>
</html>
