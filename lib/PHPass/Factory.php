<?php
namespace Phpass;

use Phpass\Exception\InvalidArgumentException;
use Phpass\Hash\Adapter\Bcrypt;
use Phpass\Hash\Adapter\ExtDes;
use Phpass\Hash\Adapter\Md5Crypt;
use Phpass\Hash\Adapter\Pbkdf2;
use Phpass\Hash\Adapter\Portable;
use Phpass\Hash\Adapter\Sha1Crypt;
use Phpass\Hash\Adapter\Sha256Crypt;
use Phpass\Hash\Adapter\Sha512Crypt;

class Factory
{
	public static function create(array $options)
	{
		$options += array(
			'adapter' => 'bcrypt',
			'iterationcountlog2' => 12,
			'identifier' => '2y'
		);
		switch(strtolower($options['adapter'])) {
			case 'bcrypt': $adapter = new Bcrypt( $options ); break;
			case 'extdes': $adapter = new ExtDes( $options ); break;
			case 'pbkdf2': $adapter = new Pbkdf2( $options ); break;
			case 'portable': $adapter = new Portable( $options ); break;
			case 'md5crypt':
			case 'md5': $adapter = new Md5Crypt( $options ); break;
			case 'sha1crypt':
			case 'sha1': $adapter = new Sha1Crypt( $options ); break;
			case 'sha256crypt':
			case 'sha256': $adapter = new Sha256Crypt( $options ); break;
			case 'sha512crypt':
			case 'sha512': $adapter = new Sha512Crypt( $options ); break;
			default:
				throw new InvalidArgumentException("Value of key 'adapter' must be an instance of Phpass\\Hash\\Adapter.");
		}
		return $adapter;
	}
}
