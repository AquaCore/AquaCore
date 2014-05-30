<?php
namespace Aqua\Ragnarok;

use Aqua\SQL\Query;

class Guild
{
	/**
	 * @var \Aqua\Ragnarok\Server\CharMap
	 */
	public $charmap;
	/**
	 * @var int
	 */
	public $id;
	/**
	 * @var string
	 */
	public $name;
	/**
	 * @var int
	 */
	public $level;
	/**
	 * @var int
	 */
	public $experience;
	/**
	 * @var int
	 */
	public $nextExperience;
	/**
	 * @var int
	 */
	public $averageLevel;
	/**
	 * @var int
	 */
	public $leaderId;
	/**
	 * @var string
	 */
	public $leaderName;
	/**
	 * @var int
	 */
	public $memberLimit;
	/**
	 * @var int
	 */
	public $memberCount;
	/**
	 * @var int
	 */
	public $castleCount;
	/**
	 * @var int
	 */
	public $skillPoints;
	/**
	 * @var int
	 */
	public $online;
	/**
	 * @var int
	 */
	public $message = array( '', '' );

	public function emblem()
	{
		return ac_guild_emblem($this->charmap->server->key, $this->charmap->key, $this->id);
	}

	/**
	 * @return \Aqua\SQL\Search
	 */
	public function memberSearch()
	{
		$columns = array(
			'id'                => 'gm.char_id',
			'slot'              => 'c.char_num',
			'name'              => 'gm.name',
			'class'             => 'c.class',
			'account_id'        => 'gm.account_id',
			'base_level'        => 'c.base_level',
			'job_level'         => 'c.job_level',
			'base_experience'   => 'c.base_exp',
			'job_experience'    => 'c.job_exp',
			'zeny'              => 'c.zeny',
			'party_id'          => 'c.party_id',
			'guild_id'          => 'gm.guild_id',
			'online'            => 'c.online',
			'last_map'          => 'c.last_map',
			'last_x'            => 'c.last_x',
			'last_y'            => 'c.last_y',
			'save_map'          => 'c.save_map',
			'save_x'            => 'c.save_x',
			'save_y'            => 'c.save_y',
			'fame'              => 'c.fame',
			'option'            => 'c.option',
			'karma'             => 'c.karma',
			'manner'            => 'c.manner',
			'str'               => 'c.str',
			'vit'               => 'c.vit',
			'agi'               => 'c.agi',
			'dex'               => 'c.dex',
			'int'               => 'c.int',
			'luk'               => 'c.luk',
			'max_hp'            => 'c.max_hp',
			'max_sp'            => 'c.max_sp',
			'hp'                => 'c.hp',
			'sp'                => 'c.sp',
			'status_point'      => 'c.status_point',
			'skill_point'       => 'c.skill_point',
			'pet_id'            => 'c.pet_id',
			'homunculus_id'     => 'c.homun_id',
			'elemental_id'      => 'c.elemental_id',
			'partner_id'        => 'c.partner_id',
			'father_id'         => 'c.father',
			'mother_id'         => 'c.mother',
			'child_id'          => 'c.child',
			'cp_options'        => 'c.ac_options',
			'delete_date'       => 'c.delete_date',
		    'sex'               => 'gm.gender',
		    'guild_exp'         => 'gm.exp',
		    'guild_exp_payper'  => 'gm.exp_payper',
		    'guild_position'    => 'gm.position',
		    'guild_name'        => '\'\''
		);
		return Query::search($this->charmap->connection())
			->columns($columns)
			->setColumnType(array(
				'guild_exp'        => 'integer',
			    'guild_exp_payper' => 'integer',
			    'guild_position'   => 'integer'
			))
			->whereOptions($columns)
			->from($this->charmap->table('guild_member'), 'gm')
			->innerJoin($this->charmap->table('char'), 'c.char_id = gm.char_id', 'c')
			->groupBy('gm.char_id')
			->where(array( 'guild_id' => $this->id ))
			->parser(array( $this->charmap, 'parseCharSql' ), array(array( 'guild_exp', 'guild_exp_payper', 'guild_position' )));
	}

	public function alliances()
	{
		$sth = $this->charmap->connection()->prepare(sprintf('
		SELECT alliance_id, opposition, `name`
		FROM %s
		WHERE guild_id = ?
		', $this->charmap->table('guild_alliance')));
		$sth->bindValue(1, $this->id, \PDO::PARAM_INT);
		$sth->execute();
		$alliances = array();
		foreach($sth->fetchAll(\PDO::FETCH_NUM) as $column) {
			$alliances[$column[0]] = array(
				'opposition' => (int)$column[1],
				'name'       => $column[2]
			);
		}
		return $alliances;
	}

	public function skills()
	{
		$sth = $this->charmap->connection()->prepare(sprintf('
		SELECT id, lv FROM %s WHERE guild_id = ?
		', $this->charmap->table('guild_skill')));
		$sth->bindValue(1, $this->id, \PDO::PARAM_INT);
		$sth->execute();
		$skills = array();
		foreach($sth->fetchAll(\PDO::FETCH_NUM) as $column) {
			$skills[$column[0]] = (int)$column[1];
		}
		return $skills;
	}

	public function positions()
	{
		$sth = $this->charmap->connection()->prepare(sprintf('
		SELECT position, `name`, `mode`, `exp_mode`
		FROM %s
		WHERE guild_id = ?
		ORDER BY position
		', $this->charmap->table('guild_position')));
		$sth->bindValue(1, $this->id, \PDO::PARAM_INT);
		$sth->execute();
		$positions = array();
		foreach($sth->fetchAll(\PDO::FETCH_NUM) as $column) {
			$positions[$column[0]] = array(
				'name' => $column[1],
				'mode' => (int)$column[2],
				'exp'  => (int)$column[3],
			);
		}
		return $positions;
	}
}
