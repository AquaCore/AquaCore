<?php
use Aqua\Core\App;
if(App::user()->loggedIn()) : ?>
<div class="ac-header-user">
	<span><?php echo __('account', 'welcome', htmlspecialchars(App::user()->account->displayName))?></span>
	<ul class="ac-user-options">
		<li>
			<a href="<?php echo ac_build_url(array('path' => array( 'account' )))?>">
				<?php echo __('account', 'my-account')?>
			</a>
		</li>
		<li>
			<a href="<?php echo ac_build_url(array('path' => array( 'account' ), 'action' => 'options'))?>">
				<?php echo __('profile', 'preferences')?>
			</a>
		</li>
		<li>
			<a href="<?php echo ac_build_url(array('path' => array( 'account' ), 'action' => 'logout'))?>">
				<?php echo __('login', 'logout')?>
			</a>
		</li>
		<?php if(App::user()->role()->hasPermission('view-admin-cp')) : ?>
		<li>
			<a href="<?php echo \Aqua\URL ?>/admin">
				<?php echo __('application', 'cp-title')?>
			</a>
		</li>
		<?php endif; ?>
	</ul>
</div>
<?php else : ?>
<div class="ac-header-login">
	<form method="POST" action="<?php echo ac_build_url(array(
		'protocol' => \Aqua\HTTPS || App::settings()->get('ssl', 0) >= 1 ? 'https://' : 'http://',
		'path'     => array( 'account' ),
		'action'   => 'login'
	))?>">
		<div class="ac-login-inputs">
			<input type="text" name="username" placeholder="<?php echo __('profile', 'username') ?>">
			<input type="password" name="password" placeholder="<?php echo __('profile', 'password') ?>">
		</div>
		<input type="hidden" name="return_url" value="<?php echo App::request()->uri->url() ?>">
		<input type="hidden" name="account_login" value="<?php echo App::user()->setToken('account_login')?>">
		<input type="submit" value="OK" class="ac-login-submit">
	</form>
	<ul class="ac-login-options">
		<li>
			<a href="<?php echo ac_build_url(array('path' => array( 'account' ), 'action' => 'register'))?>">
				<?php echo __('registration', 'register')?>
			</a>
		</li><li>
			<a href="<?php echo ac_build_url(array('path' => array( 'account' ), 'action' => 'recoverpw'))?>">
				<?php echo __('reset-pw', 'recover-password')?>
			</a>
		</li>
	</ul>
</div>
<?php endif; ?>
