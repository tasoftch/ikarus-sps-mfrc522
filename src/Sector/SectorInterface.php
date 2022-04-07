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

namespace Ikarus\MiFare\Sector;


use Ikarus\MiFare\Authentication\AuthenticationInterface;

interface SectorInterface
{
	const SECTOR_ID_00 = 0;
	const SECTOR_ID_01 = 1;
	const SECTOR_ID_02 = 2;
	const SECTOR_ID_03 = 3;
	const SECTOR_ID_04 = 4;
	const SECTOR_ID_05 = 5;
	const SECTOR_ID_06 = 6;
	const SECTOR_ID_07 = 7;
	const SECTOR_ID_08 = 8;
	const SECTOR_ID_09 = 9;
	const SECTOR_ID_10 = 10;
	const SECTOR_ID_11 = 11;
	const SECTOR_ID_12 = 12;
	const SECTOR_ID_13 = 13;
	const SECTOR_ID_14 = 14;
	const SECTOR_ID_15 = 15;
	const SECTOR_ID_16 = 16;
	const SECTOR_ID_17 = 17;
	const SECTOR_ID_18 = 18;
	const SECTOR_ID_19 = 19;
	const SECTOR_ID_20 = 20;
	const SECTOR_ID_21 = 21;
	const SECTOR_ID_22 = 22;
	const SECTOR_ID_23 = 23;
	const SECTOR_ID_24 = 24;
	const SECTOR_ID_25 = 25;
	const SECTOR_ID_26 = 26;
	const SECTOR_ID_27 = 27;
	const SECTOR_ID_28 = 28;
	const SECTOR_ID_29 = 29;
	const SECTOR_ID_30 = 30;
	const SECTOR_ID_31 = 31;

	/**
	 * Returns the requested sector ID
	 *
	 * @return int
	 */
	public function getSectorID(): int;

	/**
	 * Returns 48 bytes from sector (blocks 0 - 2)
	 *
	 * @return array
	 */
	public function getBytes(): array;

	/**
	 * Returns the 48 bytes as string
	 *
	 * @return string
	 */
	public function getString(): string;

	/**
	 * Gets the authentication whether for reading or writing.
	 *
	 * @return AuthenticationInterface
	 */
	public function getAuthentication(): AuthenticationInterface;
}