<?php
namespace Aqua\Schedule\Task;

use Aqua\Core\App;
use Aqua\Schedule\AbstractTask;
use Aqua\SQL\Query;
use Aqua\SQL\Select;
use Aqua\Util\Email;

class BulkMailTask
extends AbstractTask
{
	protected function run()
	{
		$settings = App::settings()->get('email');
		$emails = Query::select(App::connection())
			->columns(array(
				'id'       => 'id',
				'priority' => '_priority',
			    'fromName' => '_from_name',
			    'fromAddr' => '_from_address',
			    'toName'   => '_to_name',
			    'toAddr'   => '_to_address',
			    'subject'  => '_subject',
			    'content'  => '_content'
			))
			->setColumnType(array( 'id' => 'integer' ))
			->from(ac_table('mail_queue'))
			->where(array( '_status' => 'pending' ))
			->order(array( '_date' => 'DESC' ))
			->setKey('id');
		if($limit = $settings->get('max_emails_per_run', 50)) {
			$emails->limit($limit);
		}
		$emails->query();
		if(!$emails->count()) {
			$this->abort();
			return;
		}
		$emailIds = $emails->getColumn('id');
		array_unshift($emailIds, Select::SEARCH_IN);
		Query::update(App::connection())
			->tables(array( 'm' => ac_table('mail_queue') ))
			->set(array( 'm._status' => 'processing' ))
			->where(array( 'm.id' => $emailIds ))
			->query();
		$ccSearch = Query::select(App::connection())
			->columns(array(
				'id'      => '_mail_id',
			    'name'    => '_name',
			    'address' => '_address',
			    'bcc'     => '_bcc'
			))
			->where(array( '_mail_id' => $emailIds ))
			->setColumnType(array( 'id' => 'integer' ))
			->from(ac_table('mail_cc'))
			->query();
		array_shift($emailIds);
		$cc = $bcc = array_fill_keys($emailIds, array());
		$success = $failure = 0;
		foreach($ccSearch as $data) {
			if($data['bcc'] === 'y') {
				$array = &$bcc;
			} else {
				$array = &$cc;
			}
			$array[$data['id']][$data['address']] = $data['name'];
		}
		unset($ccSearch, $data, $array);
		Email::phpMailer()->SMTPKeepAlive = true;
		Email::phpMailer()->SMTPDebug     = 1;
		ob_start();
		foreach($emails as $id => $data) {
			$email = new Email($data['subject'],
			                   $data['content'],
			                   $data['toAddr'],
			                   $data['toName']);
			$email->setFrom($data['fromAddr'], $data['fromName'])
				->addCC($cc[$id])
				->addBCC($bcc[$id])
				->setPriority($data['priority']);
			try {
				if($email->send()) {
					++$success;
				} else {
					++$failure;
				}
			} catch(\Exception $e) {
				++$failure;
			}
		}
		$outputFull  = ob_get_contents();
		$outputShort = "$success emails sent;\r\n" .
		               "$failure emails failed;\r\n";
		ob_end_clean();
		Email::phpMailer()->smtpClose();
		Email::phpMailer()->SMTPKeepAlive = false;
		Email::phpMailer()->SMTPDebug     = 0;
		$sth = App::connection()->prepare(sprintf('
		DELETE FROM `%s`
		WHERE id IN (' . str_repeat('?,', count($emailIds) - 1) . '?)
		', ac_table('mail_queue')));
		$sth->execute($emailIds);
		$this->endTask($outputShort, $outputFull);
	}
}
