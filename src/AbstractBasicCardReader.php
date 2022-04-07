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

namespace Ikarus\MiFare;


use Ikarus\MiFare\Authentication\AuthenticationContainer;
use Ikarus\MiFare\Authentication\AuthenticationContainerInterface;
use Ikarus\MiFare\Authentication\AuthenticationInterface;
use Ikarus\MiFare\Authentication\BasicAuthentication;
use Ikarus\MiFare\Sector\MutableSectorInterface;
use Ikarus\MiFare\Sector\SectorInterface;
use Ikarus\MiFare\UID\AbstractCardID;
use Ikarus\MiFare\UID\CardIDInterface;

abstract class AbstractBasicCardReader implements CardReaderInterface
{
	const MI_OK = 0;
	const MI_NOTAGERR = 1;
	const MI_ERR = 2;

	const PICC_REQIDL = 0x26;
	const PICC_REQALL = 0x52;
	const PICC_ANTICOLL = 0x93;
	const PICC_SElECTTAG = 0x93;
	const PICC_AUTHENT1A = 0x60;
	const PICC_AUTHENT1B = 0x61;
	const PICC_READ = 0x30;
	const PICC_WRITE = 0xA0;
	const PICC_DECREMENT = 0xC0;
	const PICC_INCREMENT = 0xC1;
	const PICC_RESTORE = 0xC2;
	const PICC_TRANSFER = 0xB0;
	const PICC_HALT = 0x50;


	/**
	 * @param float|int $timeout
	 * @return CardIDInterface|null
	 */
	public function readCardID(float $timeout = 0): ?CardIDInterface
	{
		$start = microtime(true) + $timeout;
		restart:

		list($status) = $this->request( self::PICC_REQIDL );

		if($status != self::MI_OK)
			goto complete;
		list($status, $uid) = $this->anticoll();
		if($status != self::MI_OK)
			goto complete;

		return AbstractCardID::makeID($uid);

		complete:
		if(microtime(true) < $start) {
			usleep(1000);
			goto restart;
		}

		return NULL;
	}

	/**
	 * Sends a request to the card reader
	 *
	 * @param int $mode
	 * @return array
	 */
	abstract protected function request(int $mode): array;

	/**
	 * Creates anti-collision environment
	 *
	 * @return array
	 */
	abstract protected function anticoll(): array;

	/**
	 * selects a given badge or card
	 *
	 * @param array $cardID
	 */
	abstract protected function selectCardID(array $cardID);

	/**
	 * @param array $cardID
	 * @param int $authMode
	 * @param int $blockAddr
	 * @param array $key
	 * @return int
	 */
	abstract protected function authenticate(array $cardID, int $authMode, int $blockAddr, array $key): int;

	/**
	 * Terminates the authenticated environment
	 */
	abstract protected function stopCrypto();

	/**
	 * Reads 16 bytes from block address
	 *
	 * @param int $blockAddr
	 * @return array
	 */
	abstract protected function read(int $blockAddr): array;

	/**
	 * @param int $blockAddr
	 * @param array $data
	 * @return int
	 */
	abstract protected function write(int $blockAddr, array $data): int;

	/**
	 * @param CardIDInterface $cardID
	 * @param MutableSectorInterface $sector
	 * @return int
	 */
	public function readCardSector(CardIDInterface $cardID, MutableSectorInterface $sector): int
	{
		$status = $this->_select_and_authenticate_card($cardID, $sector);

		if($status != self::MI_OK)
			return $status;

		for($e=0;$e<3;$e++) {
			$block = $this->read( $sector->getSectorID() * 4 + $e );
			if($block)
				$sector->appendBytes($block);
		}
		$this->stopCrypto();
		return self::MI_OK;
	}

	public function writeCardSector(CardIDInterface $cardID, SectorInterface $sector): int
	{
		$status = $this->_select_and_authenticate_card($cardID, $sector);
		$this->read( $sector->getSectorID() * 4 + 3 );

		if($status == self::MI_OK) {
			$data_array = $sector->getBytes();

			for($i=0;$i<3;$i++) {
				$d = array_slice($data_array, $i*16, 16);
				if($d)
					$this->write($sector->getSectorID()*4+$i, $d);
			}
		}
		$this->stopCrypto();
		return self::MI_OK;
	}


	/**
	 * @param CardIDInterface $cardID
	 * @param SectorInterface $sector
	 * @return AuthenticationContainerInterface|null
	 */
	public function readAuthentication(CardIDInterface $cardID, SectorInterface $sector): ?AuthenticationContainerInterface
	{
		$status = $this->_select_and_authenticate_card($cardID, $sector);

		if($status != self::MI_OK)
			return NULL;

		$block = $this->read( $sector->getSectorID() * 4 + 3 );
		if($block) {
			$A = array_slice($block, 0, 6);
			$B = array_slice($block, 10, 6);
			$bits = array_slice($block, 6, 4);

			// TODO: Resolve access bits
			return new AuthenticationContainer([
				BasicAuthentication::A($A),
				BasicAuthentication::B($B)
			]);
		}
		return NULL;
	}

	public function writeAuthentication(CardIDInterface $cardID, AuthenticationContainerInterface $authenticationContainer, SectorInterface $toSector): bool
	{
		$status = $this->_select_and_authenticate_card($cardID, $toSector);

		if($status == self::MI_OK) {
			$data = array_merge(
				[],
				$authenticationContainer->getAuthentication( AuthenticationInterface::AUTH_TYPE_A )->getKey(),
				$authenticationContainer->getAccessBits(),
				$authenticationContainer->getAuthentication( AuthenticationInterface::AUTH_TYPE_B )->getKey()
			);

			$status = $this->write($toSector->getSectorID() * 4 + 3, $data);
		}
		$this->stopCrypto();
		return $status;
	}

	private function _select_and_authenticate_card(CardIDInterface $cardID, SectorInterface $sector): int {
		$this->selectCardID(array_merge($cardID->getIdentifierBytes(), [$cardID->getChecksum()]));

		return $this->authenticate(
			$cardID->getIdentifierBytes(),
			(function($t) {
				switch ($t) {
					case AuthenticationInterface::AUTH_TYPE_A: return self::PICC_AUTHENT1A;
					case AuthenticationInterface::AUTH_TYPE_B: return self::PICC_AUTHENT1B;
				}
				return 0;
			})($sector->getAuthentication()->getAuthenticationType()),
			$sector->getSectorID() * 4 + 3,
			$sector->getAuthentication()->getKey()
		);
	}
}