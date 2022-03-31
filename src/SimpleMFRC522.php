<?php
/*
 * Copyright (c) 2022 TASoft Applications, Th. Abplanalp <info@tasoft.ch>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Ikarus\SPS\SPI;


class SimpleMFRC522 extends BasicMFRC522
{
	protected $KEY = [0xFF,0xFF,0xFF,0xFF,0xFF,0xFF];
	protected $BLOCK_ADDRS = [8, 9, 10];

	protected $AUTH_BLOCK_ADDR = 11;

	/**
	 * @return int[]
	 */
	public function getKey(): array
	{
		return $this->KEY;
	}

	/**
	 * @return int[]
	 */
	public function getBlockAddresses(): array
	{
		return $this->BLOCK_ADDRS;
	}

	/**
	 * @return int
	 */
	public function getAuthBlockAddress(): int
	{
		return $this->AUTH_BLOCK_ADDR;
	}

	/**
	 * @param int[] $KEY
	 * @return SimpleMFRC522
	 */
	public function setKey(...$KEY): SimpleMFRC522
	{
		for($e=0;$e<6;$e++)
			$this->KEY[$e] = $KEY[$e] ?? 0xFF;
		return $this;
	}

	/**
	 * @param int[] $BLOCK_ADDRS
	 * @return SimpleMFRC522
	 */
	public function setBlockAddresses(array $BLOCK_ADDRS): SimpleMFRC522
	{
		$this->BLOCK_ADDRS = $BLOCK_ADDRS;
		return $this;
	}

	/**
	 * @param int $AUTH_BLOCK_ADDR
	 * @return SimpleMFRC522
	 */
	public function setAuthBlockAddress(int $AUTH_BLOCK_ADDR): SimpleMFRC522
	{
		$this->AUTH_BLOCK_ADDR = $AUTH_BLOCK_ADDR;
		return $this;
	}


	/**
	 *
	 * @param float $timeout Timeout in seconds
	 */
	private function _readCardID(&$uid, float $timeout = 0) {
		$start = microtime(true) + $timeout;
		restart:

		list($status) = $this->request( self::PICC_REQIDL );

		if($status != self::MI_OK)
			goto complete;
		list($status, $uid) = $this->anticoll();
		if($status != self::MI_OK)
			goto complete;

		return uint64_array_to_string( $uid );
		complete:
		if(microtime(true) < $start)
			goto restart;
		$uid = NULL;
		return -1;
	}

	/**
	 * Reads the UUID from a badge or a card
	 * @param float $timeout Timeout in seconds
	 * @return int|string
	 */
	public function readCardID(float $timeout = 0)
	{
		return $this->_readCardID($n, $timeout);
	}


	/**
	 * @param float|int $timeout
	 * @param string|null $uuid
	 * @param bool $asString
	 * @return array|string|null
	 */
	public function readCardContents(float $timeout = 0, string &$uuid = NULL, bool $asString = false) {
		$uuid = $this->_readCardID($uid, $timeout);
		if($uuid == -1)
			return NULL;
		$this->selectTag( $uid );
		$status = $this->authenticate($uid, self::PICC_AUTHENT1A, $this->AUTH_BLOCK_ADDR, $this->KEY);
		if($status != self::MI_OK)
			return NULL;

		$data = [];
		foreach($this->BLOCK_ADDRS as $addr) {
			$block = $this->read( $addr );
			if($block)
				$data = array_merge($data, $block);
		}
		if($asString)
			return join("", array_map(function($c) { return chr( $c ); }, $data));
		return $data;
	}

	/**
	 * @param $data
	 * @param float|int $timeout
	 * @param string|null $uuid
	 * @return bool|string
	 */
	public function writeCardContents($data, float $timeout = 0, string &$uuid = NULL) {
		$uuid = $this->_readCardID($uid, $timeout);
		if($uuid == -1)
			return false;
		$this->selectTag( $uid );
		$status = $this->authenticate($uid, self::PICC_AUTHENT1A, $this->AUTH_BLOCK_ADDR, $this->KEY);
		$this->read($this->AUTH_BLOCK_ADDR);

		if(is_array($data)) {
			$data = join("", array_map(function($c){return chr($c);}, $data));
		}

		if($status == self::MI_OK) {
			$data_array = array_map(function($c){return ord($c);}, str_split($data));

			for($i=0;$i<count($this->BLOCK_ADDRS);$i++) {
				$d = array_slice($data_array, $i*16, ($i+1)*16);
				if($d)
					$this->write($this->BLOCK_ADDRS[$i], $d);
			}
		}
		$this->stopCrypro();
		return substr($data, 0, count($this->BLOCK_ADDRS)*16);
	}
}