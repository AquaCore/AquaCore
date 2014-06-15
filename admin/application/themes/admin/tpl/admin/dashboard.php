<?php
use Aqua\Core\App;
use Aqua\Content\ContentType;
use Aqua\Content\Filter\CommentFilter;
use Aqua\Log\PayPalLog;
use Aqua\Log\TransferLog;
use Aqua\UI\ScriptManager;
use Aqua\UI\Dashboard;
use Aqua\User\Account;
use Aqua\User\Role;
/**
 * @var $page \Page\Admin
 */
Account::rebuildCache('last_user');
$account_cache = Account::fetchCache();
$page->theme->footer->enqueueScript(ScriptManager::script('highsoft.highchart'));
$page->theme->footer->enqueueScript('theme.dashboard')
	->type('text/javascript')
	->src($page->theme->url . '/scripts/dashboard.js');
$page->theme
	->addWordGroup('week')
	->addWordGroup('dashboard', array( 'this-week', 'weeks-ago-s', 'weeks-ago-p', 'donation-goal' ))
	->addSettings('registrationStats', $account_cache['reg_stats']);
$datetime_format = App::settings()->get('datetime_format');
$base_acc_url = ac_build_url(array(
		'path' => array( 'user' ),
		'action' => 'view',
		'arguments' => array( '' )
	));
$dash = new Dashboard;
$user_stats = array();
for($i = 0; $i < Account::CACHE_LATEST_ACCOUNTS; ++$i) {
	if(!isset($account_cache['last_user'][$i])) {
		$user_stats[] = '';
	} else {
		$user = $account_cache['last_user'][$i];
		$user_stats[] = '<img class="ac-user-avatar" src="' . $user['avatar'] . '"><div class="ac-user-data">' .
			'<a class="ac-user-display" href="' . $base_acc_url . $user['id'] . '">' .
			Role::get($user['role_id'])->display($user['display_name'], 'ac-username')->render() .
			'</a><div class="ac-user-registration-date">'
			. strftime($datetime_format, $user['registration_date']) . '</div></div>';
	}
}
$dash->get('account')
	->append('registration-stats', array(
		'span' => 2,
		'title' => __('dashboard', 'registration-stats'),
		'class' => 'ac-reg-stats',
		'content' => '<div id="reg-stats"></div>'
	))
	->append('new-users', array(
		'title' => __('dashboard', 'last-users'),
		'class' => 'ac-user-stats',
		'content' => $user_stats
	));
$pp_cache = PayPalLog::fetchCache();
$donations = array();
$donation_settings = App::settings()->get('donation');
$base_txn_url = ac_build_url(array(
		'path' => array( 'log' ),
		'action' => 'viewpaypal',
		'arguments' => array( '' )
	));
if($goal = $donation_settings->get('goal', 0)) {
	$cur = strtoupper($donation_settings->get('currency'));
	$pct = round(($pp_cache['goal']['total'] / $goal) * 100, 3);
	$x = floor($pp_cache['goal']['total'] / $goal);
	$alt = htmlspecialchars(__('dashboard', 'donation-goal-tooltip', number_format($goal), $cur, __('timespan', $donation_settings->get('goal_interval'))));
	$donations[] =
		'<div class="ac-donation-goal ac-tooltip" title="' . $alt . '" alt="' . $alt . '">' .
		'<div class="ac-donation-goal-meter progress-' . $x . '">' .
		'<div class="ac-donation-goal-amount">' .
		$pct . '% <span class="ac-donation-amount">(' . $pp_cache['goal']['total'] . ' ' . $cur . ')</span>' .
		'</div>' .
		'<div class="ac-donation-goal-progress" style="width: ' . $pct . '%">' .
		'</div>' .
		'</div>' .
		'</div>';
}
if(empty($pp_cache['last_donation'])) {
	$donations[] = '<div style="text-align: center">' . __('application', 'no-search-results') . '</div>';
} else for($i = 0; $i < PayPalLog::CACHE_RECENT_DONATIONS; ++$i ) {
	if(!isset($pp_cache['last_donation'][$i])) {
		$donations[] = '';
	} else {
		$donation = $pp_cache['last_donation'][$i];
		if($donation['user_id']) {
			$name = '<a class="ac-user-display" href="' . $base_acc_url . $donation['user_id'] . '">';
			$name.= Role::get($donation['role_id'])->display($donation['display_name'], 'ac-username');
			$name.= '</a>';
		} else {
			$role = Role::get(Role::ROLE_GUEST);
			$name = $role->display($role->name, 'ac-guest');
		}
		$donations[] =
			'<div class="ac-donation-info">' .$name . '<div class="ac-donation-date">' .
			strftime($datetime_format, $donation['process_date']) .
			'</div></div><div class="ac-donation-exchange">' .
			number_format($donation['gross'], 2) . ' <small>' . $donation['currency'] .
			'</small> <span>&rarr;</span> ' .
			__('donation', 'credit-points', number_format($donation['credits'])) .
			'</div><a class="ac-donation-txn" href="' . $base_txn_url . $donation['id'] . '">' . __('dashboard', 'txn-info') . '</a>';
	}
}
$transfer_cache = TransferLog::fetchCache('last_transfer');
$transfers = array();
if(empty($transfer_cache)) {
	$transfers = '<div style="text-align: center">' . __('application', 'no-search-results') . '</div>';
} else for($i = 0; $i < TransferLog::CACHE_RECENT_TRANSFERS; ++$i) {
	if(empty($transfer_cache[$i])) {
		$transfers[]= '';
	} else {
		$xfer = $transfer_cache[$i];
		$transfers[] = array(
			'<a href="' . $base_acc_url . $xfer['sender'] . '">' .
			Role::get($xfer['sender_role_id'])->display($xfer['sender_display_name'], 'ac-username') .
			'</a>',
			'<a href="' . $base_acc_url . $xfer['receiver'] . '">' .
			Role::get($xfer['receiver_role_id'])->display($xfer['receiver_display_name'], 'ac-username') .
			'</a>',
			__('donation', 'credit-points', number_format($xfer['amount'])),
			strftime($datetime_format, $xfer['date'])
		);
	}
}
$dash->get('donation')
	->append('donation', array(
		'title' => __('dashboard', 'last-donations'),
		'class' => 'ac-donation',
		'content' => $donations
	))
	->append('transfer', array(
		'span' => 2,
		'title' => __('dashboard', 'last-transfers'),
		'class' => 'ac-credit-xfer',
		'header' => array(
			__('donation', 'sender'),
			__('donation', 'receiver'),
			__('donation', 'amount'),
			__('donation', 'xfer-date')
		),
		'content' => $transfers
	));
$commentCache = CommentFilter::fetchCache();
$comments = $reports = array();
if(empty($commentCache['last_comments'])) {
	$comments = '<div style="text-align: center">' . __('application', 'no-search-results') . '</div>';
} else for($i = 0; $i < CommentFilter::CACHE_RECENT_COMMENTS; ++$i) {
	if(empty($commentCache['last_comments'][$i])) {
		$comments[] = '';
	} else {
		$comment = $commentCache['last_comments'][$i];
		$cType = ContentType::getContentType($comment['content_type']);
		$comments[] = '<div class="ac-comment"><div class="ac-comment-avatar"><img src="' . $comment['avatar'] . '"></div>' .
		              '<div class="ac-comment-header">' .
		              __('comment', 'info', '<a href="' . $base_acc_url . $comment['user_id'] . '">' .
		              Role::get($comment['role_id'])->display($comment['display_name'], 'ac-username') .
		              '</a>', __('time-elapsed', 'ago', ac_time_elapsed($comment['publish_date']))) . '</div>' .
		              '<div class="ac-comment-content">' . $comment['content'] . '<div class="ac-comment-padding"></div></div><div class="ac-comment-url">' .
		              '<a href="' . $cType->url(array(
	                        'path' => array( $comment['slug'] ),
	                        'query' => array( 'root' => $comment['id'] ),
	                        'hash' => 'comments'
	                    ), false) . '">' . htmlspecialchars($comment['title']) . ' #' . $comment['id'] . '</a>' .
		              '</div></div>';
	}
}
if(empty($commentCache['last_reports'])) {
	$comments = '<div style="text-align: center">' . __('application', 'no-search-results') . '</div>';
} else for($i = 0; $i < CommentFilter::CACHE_RECENT_REPORTS; ++$i) {
	if(empty($commentCache['last_reports'][$i])) {
		$reports[] = '';
	} else {
		$report = $commentCache['last_reports'][$i];
		$reports[] = array(
			'<a href="' . ac_build_url(array(
				'path' => array( 'comments' ),
			    'action' => 'edit',
			    'arguments' => array( $report['comment_id'] )
			)) . '">' . $report['comment_id'] . '</a>',
			Role::get($report['role_id'])->display($report['display_name'], 'ac-username'),
		    strftime($datetime_format, $report['date'])
		);
		if($report['content']) {
			$reports[] = array(
				'<b>' . __('comment', 'report-message') . ':</b>',
				array( '<div class="ac-report-message">' . htmlspecialchars($report['content']) . '</div>', 2 )
			);
		}
	}
}
$dash->get('comment')
	->append('comments', array(
		'title' => __('dashboard', 'last-comments'),
	    'class' => 'ac-comments',
	    'content' => $comments
	))
	->append('reports', array(
		'title' => __('dashboard', 'last-reports'),
	    'class' => 'ac-reports',
	    'content' => $reports,
	    'header' => array(
		    __('comment', 'id'),
		    __('profile', 'user'),
		    __('comment', 'date')
	    )
	));
echo '<div class="ac-dashboard">', $dash->render(), '</div>';
