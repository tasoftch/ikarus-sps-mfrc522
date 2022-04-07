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


use Ikarus\MiFare\Authentication\AuthenticationContainerInterface;
use Ikarus\MiFare\Sector\MutableSectorInterface;
use Ikarus\MiFare\Sector\SectorInterface;
use Ikarus\MiFare\UID\CardIDInterface;

interface CardReaderInterface
{
	/**
	 * Switches the antenna on
	 */
	public function antennaOn();

	/**
	 * Switches the antenna off
	 */
	public function antennaOff();

	/**
	 * Reads the ID of a card or badge near the sensor.
	 *
	 * @param float|int $timeout
	 * @return CardIDInterface|null
	 */
	public function readCardID(float $timeout = 0): ?CardIDInterface;

	/**
	 * @param CardIDInterface $cardID
	 * @param MutableSectorInterface $sector
	 * @return int
	 */
	public function readCardSector(CardIDInterface $cardID, MutableSectorInterface $sector): int;

	/**
	 * @param CardIDInterface $cardID
	 * @param SectorInterface $sector
	 * @return int
	 */
	public function writeCardSector(CardIDInterface $cardID, SectorInterface $sector): int;

	/**
	 * @param CardIDInterface $cardID
	 * @param SectorInterface $sector
	 * @return AuthenticationContainerInterface|null
	 */
	public function readAuthentication(CardIDInterface $cardID, SectorInterface $sector): ?AuthenticationContainerInterface;

	/**
	 * @param CardIDInterface $cardID
	 * @param AuthenticationContainerInterface $authenticationContainer
	 * @param SectorInterface $toSector
	 * @return bool
	 */
	public function writeAuthentication(CardIDInterface $cardID, AuthenticationContainerInterface $authenticationContainer, SectorInterface $toSector): bool;
}