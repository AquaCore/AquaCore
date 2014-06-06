<?php
namespace Page\Admin;

use Aqua\Core\App;
use Aqua\Core\L10n;
use Aqua\Log\ErrorLog;
use Aqua\Log\PayPalLog;
use Aqua\Log\ProfileUpdateLog;
use Aqua\Ragnarok\Server;
use Aqua\Ragnarok\Account as RoAccount;
use Aqua\Site\Page;
use Aqua\UI\Form;
use Aqua\UI\Pagination;
use Aqua\UI\Template;
use Aqua\UI\Theme;
use Aqua\User\Account;
use Aqua\User\Role;
use Aqua\Util\ImageUploader;

class User
extends Page
{
	public static $usersPerPage = 20;
	public static $logsPerPage  = 20;

	public function index_action()
	{
		$this->theme->head->section = $this->title = __('admin-menu', 'users');
		try {
			$currentPage = $this->request->uri->getInt('page', 1, 1);
			$frm = new \Aqua\UI\Search(App::request(), $currentPage);
			$frm->order(array(
					'id' => 'id',
				    'uname' => 'username',
				    'display' => 'display_name',
				    'email' => 'email',
				    'role' => 'role_id',
				    'status' => 'status',
				    'regdate' => 'registration_date'
				))
				->limit(0, 6, 20, 5)
				->defaultOrder('id')
				->defaultLimit(20)
				->persist('admin.users');
			$frm->input('uname')
				->setColumn('username')
				->setLabel(__('profile', 'username'));
			$frm->input('display')
				->setColumn('display_name')
				->setLabel(__('profile', 'display-name'));
			$frm->input('email')
				->setColumn('email')
				->setLabel(__('profile', 'email'));
			$frm->range('reg')
				->setColumn('registration_date')
				->setLabel(__('profile', 'registration-date'))
				->type('datetime')
				->attr('placeholder', 'YYY-MM-DD HH:MM:SS');
			$roles = array();
			foreach(Role::$roles as $role) {
				$roles[$role->id] = htmlspecialchars($role->name);
			}
			$frm->select('role')
				->setColumn('role_id')
				->multiple()
				->setLabel(__('profile', 'role'))
				->value($roles);
			$frm->select('status')
				->setColumn('status')
				->multiple()
				->setLabel(__('profile', 'status'))
				->value(L10n::rangeList('account-state', range( 0, 3 )));
			$search = Account::search()->calcRows(true);
			$frm->apply($search);
			$search->query();
			$pgn = new Pagination(App::user()->request->uri,
			                      ceil($search->rowsFound / $frm->getLimit()),
			                      $currentPage);
			$tpl = new Template;
			$tpl->set('users', $search->results)
				->set('userCount', $search->rowsFound)
				->set('paginator', $pgn)
				->set('search', $frm)
				->set('page', $this);
			echo $tpl->render('admin/user/search');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function view_action($id = null)
	{
		try {
			if(!$id || !($account = Account::get($id))) {
				$this->error(404);

				return;
			}
			$this->theme->head->section = $this->title = __('profile', 'view-account', htmlspecialchars($account->displayName));
			$this->theme->set('return', ac_build_url(array( 'path' => array( 'user' ) )));
			$ragnarok_accounts          = array();
			foreach(Server::$servers as $server) {
				$ragnarok_accounts = array_merge($ragnarok_accounts, $server->login->getAccounts($account));
			}
			$profileHistory  = ProfileUpdateLog::search()
				->where(array( 'user_id' => $account->id ))
				->order(array( 'date' => 'DESC' ))
				->limit(5)
				->query()
				->results;
			$donationHistory = PayPalLog::search()
				->where(array( 'user_id' => $account->id ))
				->order(array( 'process_date' => 'ASC' ))
				->limit(5)
				->query()
				->results;
			$tpl = new Template;
			$tpl->set('account', $account)
				->set('ragnarokAccounts', $ragnarok_accounts)
				->set('profileHistory', $profileHistory)
				->set('donationHistory', $donationHistory)
				->set('page', $this);
			echo $tpl->render('admin/user/view');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function edit_action($id = null)
	{
		try {
			if(!$id || !($account = Account::get($id))) {
				$this->error(404);

				return;
			}
			if(isset($this->request->data['x-delete-avatar'])) {
				do {
					$response = array( 'message' => null, 'error' => false, 'data' => array(), 'warning' => array() );
					try {
						$account->removeAvatar(true);
						$response['message'] = __('account', 'admin-account-updated');
					} catch(\Exception $exception) {
						ErrorLog::logSql($exception);
						$response['message'] = __('application', 'unexpected-error');
						$response['error']   = true;
					}
					if($this->request->ajax) {
						$this->theme = new Theme;
						$this->response->setHeader('Content-Type', 'application/json');
						$response['data'] = array( 'avatar' => null );
						echo json_encode($response);
					} else {
						$this->response->status(302)->redirect(App::request()->uri->url());
						foreach($response['warning'] as $message) {
							App::user()->addFlash('warning', null, $message);
						}
						if($response['message']) {
							App::user()->addFlash(($response['error'] ? 'error' : 'success'), null, $response['message']);
						}
					}

					return;
				} while(0);
			}
			$avatarSettings = App::settings()->get('account')->get('avatar');
			$frm = new Form($this->request);
			$frm->radio('avatar_type')
				->value(array(
					'image'    => __('profile', 'use-custom-pic'),
					'gravatar' => __('profile', 'use-gravatar')
				))
				->checked('image');
			$frm->file('image')
				->attr('accept', 'image/jpeg, image/png, image/gif')
				->maxSize(ac_size($avatarSettings->get('max_size', '2MB')) ?: null)
				->setLabel(__('profile', 'avatar'));
			$frm->input('gravatar')
				->type('text')
				->setLabel(__('profile', 'gravatar'));
			$frm->input('username')
				->type('text')
				->required()
				->value(htmlspecialchars($account->username))
				->setLabel(__('profile', 'username'));
			$frm->input('display_name')
				->type('text')
				->required()
				->value(htmlspecialchars($account->displayName))
				->setLabel(__('profile', 'display-name'));
			$frm->input('email')
				->type('email')
				->required()
				->value(htmlspecialchars($account->email))
				->setLabel(__('profile', 'email'));
			$frm->input('birthday')
				->type('date')
				->required()
				->attr('max', date('Y-m-d'))
				->value(date('Y-m-d', $account->birthDate))
				->placeholder('YYYY-MM-DD')
				->setLabel(__('profile', 'birthday'));
			$frm->input('credits')
				->type('number')
				->attr('min', 0)
				->value($account->credits)
				->setLabel(__('donation', 'credits'));
			if($account->id !== 1) {
				$roles = array();
				foreach(Role::$roles as $id => $role) {
					$roles[$id] = $role->name;
				}
				unset($roles[Role::ROLE_GUEST]);
				$frm->select('role')
					->value($roles)
					->selected($account->roleId)
					->setLabel(__('profile', 'role'));
			}
			if($account->id !== 1 || App::user()->account->id === 1) {
				$frm->input('password')
					->type('password')
					->setLabel(__('profile', 'password'));
			}
			$frm->submit();
			$frm->validate(function(Form $form) use (&$account) {
					$username = trim($form->request->getString('username'));
					$display  = trim($form->request->getString('display_name'));
					$email    = trim($form->request->getString('email'));
					$password = trim($form->request->getString('password'));
					$birthday = trim($form->request->getString('birthday'));
					$error    = false;
					if(Account::checkValidUsername($username, $message) !== Account::FIELD_OK) {
						$error = true;
						$form->field('username')->setWarning($message);
					} else if(Account::exists($username, null, null, $account->id)) {
						$error = true;
						$form->field('username')->setWarning(__('profile', 'username-taken'));
					}
					if(Account::checkValidDisplayName($display, $message) !== Account::FIELD_OK) {
						$error = true;
						$form->field('display_name')->setWarning($message);
					} else if(Account::exists(null, $display, null, $account->id)) {
						$error = true;
						$form->field('display_name')->setWarning(__('profile', 'display-name-taken'));
					}
					if(Account::checkValidEmail($email, $message) !== Account::FIELD_OK) {
						$form->field('email')->setWarning($message);
					} else if(Account::exists(null, null, $email, $account->id)) {
						$error = true;
						$form->field('email')->setWarning(__('profile', 'email-taken'));
					}
					if(!empty($password) &&
					   Account::checkValidPassword($password, $message) !== Account::FIELD_OK) {
						$error = true;
						$form->field('password')->setWarning($message);
					}
					if(!$form->field('birthday')->getWarning() &&
					   Account::checkValidBirthday(\DateTime::createFromFormat('Y-m-d', $birthday)->getTimestamp(), $message) !== Account::FIELD_OK) {
						$error = true;
						$form->field('birthday')->setWarning($message);
					}
					if($form->request->getString('avatar_type') === 'gravatar' &&
					   trim($form->request->getString('gravatar')) !== '' &&
					   filter_var(trim($form->request->getString('gravatar')), FILTER_VALIDATE_EMAIL) === false) {
						$error = true;
						$form->field('gravatar')->setWarning(__('form', 'invalid-email'));
					}

					return !($error);
				}, $this->request->ajax);
			if($frm->status !== Form::VALIDATION_SUCCESS) {
				if($this->request->ajax) {
					$this->error(204);
					return;
				}
				$this->theme->head->section = $this->title = __('profile', 'edit-account-admin', htmlspecialchars($account->displayName));
				$this->theme->set('return', ac_build_url(array(
						'path' => array( 'user' ),
				        'action' => 'view',
				        'arguments' => array( $account->id )
					)));
				$tpl = new Template;
				$tpl->set('account', $account)
					->set('form', $frm)
					->set('page', $this);
				echo $tpl->render('admin/user/edit');

				return;
			}
			$message = '';
			$error   = false;
			try {
				$update        = array();
				$avatarUpdated = false;
				if(!$frm->field('username')->getWarning()) {
					$update['username'] = trim($this->request->getString('username'));
				}
				if(!$frm->field('display_name')->getWarning()) {
					$update['display_name'] = trim($this->request->getString('display_name'));
				}
				if(!$frm->field('email')->getWarning()) {
					$update['email'] = trim($this->request->getString('email'));
				}
				if($frm->field('password') && !$frm->field('password')->getWarning()) {
					$password = trim($this->request->getString('password'));
					if(!empty($password)) {
						$update['password'] = trim($this->request->getString('password'));
					}
				}
				if(!$frm->field('birthday')->getWarning()) {
					$update['birthday'] = \DateTime::createFromFormat('Y-m-d', trim($this->request->getString('birthday')))->getTimestamp();
				}
				if(!$frm->field('credits')->getWarning()) {
					$update['credits'] = (int)trim($this->request->getString('credits'));
				}
				if($account->id !== 1 && !$frm->field('role')->getWarning()) {
					$update['role'] = (int)trim($this->request->getInt('role'));
				}
				$avatar_type = $this->request->getString('avatar_type');
				if(!$frm->field('avatar_type')->getWarning() && ac_file_uploaded('image') &&
				   !$frm->field(strtolower($avatar_type))->getWarning()) switch($avatar_type) {
					case 'image':
						$uploader = new ImageUploader;
						$uploader->maxSize(ac_size($avatarSettings->get('max_size', '2MB')) ?: null);
						$uploader->dimension($avatarSettings->get('max_width', null),
						                     $avatarSettings->get('max_height', null));
						if($uploader->uploadLocal($_FILES['image']['tmp_name'], $_FILES['image']['name']) &&
						   ($path = $uploader->save('/uploads/avatar', uniqid($account->id . '-')))) {
							$account->setAvatar($path, $_FILES['image']['name']);
						} else if($uploader->error) {
							$frm->field('image')->setWarning($uploader->errorStr());
						}
						break;
					case 'gravatar':
						$account->setGravatar($this->request->getString('gravatar'));
						$avatarUpdated = true;
						break;
				}
				if((!empty($update) && $account->update($update)) || $avatarUpdated) {
					$message = __('profile', 'admin-account-updated', htmlspecialchars($account->displayName));
				}
			} catch(\Exception $exception) {
				ErrorLog::logSql($exception);
				$error   = true;
				$message = __('application', 'unexpected-error');
			}
			if($this->request->ajax) {
				$this->theme = new Theme;
				$this->response->setHeader('Content-Type', 'application/json');
				$response = array( 'message' => $message, 'error' => $error, 'warning' => array() );
				foreach($frm->content as $key => $field) {
					if($field instanceof Form\FieldInterface && ($warning = $field->getWarning())) {
						$response['warning'][$key] = $warning;
					}
				}
				$response['data'] = array(
					'avatar'             => $account->avatar(),
					'username'           => htmlspecialchars($account->username),
					'display_name'       => htmlspecialchars($account->displayName),
					'display'            => $account->display()->render(),
					'email'              => htmlspecialchars($account->email),
					'birthday'           => date('Y-m-d', $account->birthDate),
					'formatted_birthday' => $account->birthDate(App::settings()->get('date_format')),
					'credits'            => $account->credits,
					'role'               => $account->roleId,
					'role_name'          => htmlspecialchars($account->role()->name),
					'password'           => ''
				);
				echo json_encode($response);
			} else {
				$this->response->status(302)->redirect(App::request()->uri->url());
				$user = App::user();
				foreach($frm->content as $key => $field) {
					if($field instanceof Form\FieldInterface && ($warning = $field->getWarning())) {
						$user->addFlash('warning', (($label = $field->getLabel()) ? $label : null), $warning);
					}
				}
				if($message) {
					$user->addFlash(($error ? 'error' : 'success'), null, $message);
				}
			}

			return;
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function ban_action($id = null)
	{
		try {
			if(!$id || !($account = Account::get($id)) || $account->id === 1) {
				$this->error(404);

				return;
			}
			$frm = new Form($this->request);
			if(!$account->isBanned()) {
				$frm->checkbox('ban_accounts')
					->value(array( '1' => '' ))
					->setLabel(__('profile', 'ban-accounts'));
				$frm->input('unban_time')
					->type('text')
					->setLabel(__('profile', 'unban-time'))
					->setDescription(__('profile', 'unban-time-desc'))
					->placeholder('YYYY-MM-DD hh:mm:ss');
				$frm->textarea('reason')
					->append(__('profile', 'ban-reason'));
			} else {
				$frm->checkbox('ban_accounts')
					->value(array( '1' => '' ))
					->setLabel(__('profile', 'unban-accounts'));
				$frm->textarea('reason')
					->append(__('profile', 'unban-reason'));
			}
			$frm->submit();
			$frm->validate( function (Form $frm) {
					$date = trim($frm->request->getString('unban_time'));
					if($date && (!($date = \DateTime::createFromFormat('Y-m-d H:i:s', $date)) || $date->getTimestamp() < time())) {
						$frm->field('unban_time')->setWarning(__('form', 'invalid-date'));

						return false;
					}

					return true;
				}, !$this->request->ajax);
			if($frm->status !== Form::VALIDATION_SUCCESS) {
				if($this->request->ajax) {
					$this->error(204);
					return;
				}
				if($account->isBanned()) {
					$this->title                = __('profile', 'unban-account', htmlspecialchars($account->displayName));
					$this->theme->head->section = __('profile', 'unban');
				} else {
					$this->title                = __('profile', 'ban-account', htmlspecialchars($account->displayName));
					$this->theme->head->section = __('profile', 'ban');
				}
				$this->theme->set('return', ac_build_url(array(
						'path' => array( 'user' ),
						'action' => 'view',
						'arguments' => array( $account->id )
					)));
				$tpl = new Template;
				$tpl->set('account', $account)
					->set('form', $frm)
					->set('page', $this);
				echo $tpl->render('admin/user/ban');
			}
			$message = '';
			$error   = false;
			try {
				if($frm->status === Form::VALIDATION_SUCCESS) {
					if($account->isBanned()) {
						$account->unban(App::user()->account, $this->request->getString('reason'));
						if($this->request->getInt('ban_accounts')) {
							foreach(Server::$servers as $server) {
								$sth = $server->login->connection()->prepare("
								UPDATE {$server->login->table('login')}
								SET state = :state,
									unban_time = :unban
								WHERE ac_user_id = :id
								");
								$sth->bindValue(':state', ROAccount::STATE_NORMAL, \PDO::PARAM_INT);
								$sth->bindValue(':unban', 0, \PDO::PARAM_INT);
								$sth->bindValue(':id', $account->id, \PDO::PARAM_INT);
								$sth->execute();
							}
							reset(Server::$servers);
						}
						$message = __('profile', 'account-unbanned', htmlspecialchars($account->displayName));
					} else if(!$frm->field('unban_time')->getWarning()) {
						$time = $this->request->getString('unban_time');
						if($time && ($time = \DateTime::CreateFromFormat('Y-m-d H:i:s', $time))) {
							$time = $time->getTimestamp();
						} else {
							$time = null;
						}
						$account->ban(App::user()->account, $time, $this->request->getString('reason'));
						if($this->request->getInt('ban_accounts')) {
							foreach(Server::$servers as $server) {
								$sth = $server->login->connection()->prepare("
								UPDATE {$server->login->table('login')}
								SET state = :state,
									unban_time = :unban
								WHERE ac_user_id = :id
								");
								$sth->bindValue(':state', ($time ? RoAccount::STATE_BANNED_TEMPORARILY : RoAccount::STATE_BANNED_PERMANENTLY), \PDO::PARAM_INT);
								$sth->bindValue(':unban', (int)$time, \PDO::PARAM_INT);
								$sth->bindValue(':id', $account->id, \PDO::PARAM_INT);
								$sth->execute();
							}
							reset(Server::$servers);
						}
						$message = __('profile', 'account-banned', htmlspecialchars($account->displayName));
					}
				}
			} catch(\Exception $exception) {
				ErrorLog::logSql($exception);
				$error   = true;
				$message = __('application', 'unexpected-error');
			}
			if($this->request->ajax) {
				$this->theme = new Theme;
				$this->response->setHeader('Content-Type', 'application/json');
				$response = array( 'message' => $message, 'error' => $error, 'warning' => array() );
				foreach($frm->content as $key => $field) {
					if($field instanceof Form\FieldInterface && ($warning = $field->getWarning())) {
						$response['warning'][$key] = $warning;
					}
				}
				$response['data'] = array(
					'banned'               => $account->isBanned(),
					'unban_date'           => $account->unbanDate,
					'unban_date_formatted' => $account->unbanDate(App::settings()->get('datetime_format')),
					'status'               => $account->status(),
				);
				echo json_encode($response);
			} else {
				$user = App::user();
				foreach($frm->content as $field) {
					if($field instanceof Form\FieldInterface && ($warning = $field->getWarning())) {
						$user->addFlash('warning', (($label = $field->getLabel()) ? $label : null), $warning);
					}
				}
				if($message) {
					$user->addFlash(($error ? 'error' : 'success'), null, $message);
				}
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function history_action($id = null)
	{
		try {
			if(!$id || !($account = Account::get($id))) {
				$this->error(404);

				return;
			}
			$this->theme->head->section = $this->title = __('profile-history', 'x-profile-history', htmlspecialchars($account->displayName));
			$this->theme->set('return', ac_build_url(array(
					'path' => array( 'user' ),
					'action' => 'view',
					'arguments' => array( $account->id )
				)));
			$currentPage = $this->request->uri->getInt('page', 1, 1);
			$search = ProfileUpdateLog::search()
				->calcRows(true)
				->where(array( 'user_id' => $account->id ))
				->order(array( 'date' => 'DESC' ))
				->limit(($currentPage - 1) * self::$logsPerPage, self::$logsPerPage)
				->query();
			$pgn = new Pagination(App::request()->uri, ceil($search->rowsFound / self::$logsPerPage), $currentPage);
			$tpl = new Template;
			$tpl->set('account', $account)
				->set('history', $search->results)
				->set('recordCount', $search->rowsFound)
				->set('paginator', $pgn)
				->set('page', $this);
			echo $tpl->render('admin/user/profile-history');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}
}
