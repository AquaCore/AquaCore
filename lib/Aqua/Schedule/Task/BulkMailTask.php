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
			    'type'    => '_type'
			))
			->where(array( '_mail_id' => $emailIds ))
			->setColumnType(array( 'id' => 'integer' ))
			->from(ac_table('mail_recipient'))
			->query();
		array_shift($emailIds);
		$cc = $bcc = $to = array_fill_keys($emailIds, array());
		$success = $failure = 0;
		foreach($ccSearch as $data) {
			switch($data['type']) {
				case 'bcc': $array = &$bcc; break;
				case 'cc': $array = &$cc; break;
				case 'to': $array = &$to; break;
			}
			$array[$data['id']][$data['address']] = $data['name'];
		}
		unset($ccSearch, $data, $array);
		Email::phpMailer()->SMTPKeepAlive = true;
		ob_start();
		foreach($emails as $id => $data) {
			$email = new Email($data['subject'], $data['content']);
			$email->setFrom($data['fromAddr'], $data['fromName'])
				->addAddress($to[$id])
				->addCC($cc[$id])
				->addBCC($bcc[$id])
				->setPriority($data['priority'])
				->isHtml(true);
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
		$sth = App::connection()->prepare(sprintf('
		DELETE FROM %s
		WHERE id IN (' . str_repeat('?,', count($emailIds) - 1) . '?)
		', ac_table('mail_queue')));
		$sth->execute($emailIds);
		$this->endTask($outputShort, $outputFull);
	}
}
