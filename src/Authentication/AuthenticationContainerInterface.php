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

namespace Ikarus\MiFare\Authentication;


interface AuthenticationContainerInterface
{
	/**
	 * The access constants are considered as DB_ACCESS_(C1)(C2)(C3)!
	 *
	 * Data block access bits. Valid for block 0-2.			READ	WRITE	INCREMENT	DECREMENT
	 */
	const DB_ACCESS_000				 			= 0b000; // A|B		A|B		A|B			A|B
	const BD_ACCESS_010							= 0b010; // A|B		---		---			---
	const DB_ACCESS_100							= 0b001; // A|B		B		---			---
	const DB_ACCESS_110							= 0b011; // A|B		B		B			A|B
	const DB_ACCESS_001							= 0b100; // A|B		---		---			A|B
	const DB_ACCESS_011							= 0b110; // B		B		---			---
	const DB_ACCESS_101							= 0b101; // B		---		---			---
	const DB_ACCESS_111							= 0b111; // ---		---		---			---

	const DB_ACCESS_DEFAULT = self::DB_ACCESS_000;
	const DB_ACCESS_NONE = self::DB_ACCESS_111;

	/**
	 * Trailer block access bits. 							READ	WRITE	READ	WRITE	READ	WRITE
	 */ //													KEY A			ACCESS BITS		KEY B
	const DT_ACCESS_000					 		= 0b000; // ---		A		A		---		A		A
	const BT_ACCESS_010							= 0b010; // ---		---		A		---		A		---
	const DT_ACCESS_100							= 0b001; // ---		B		A|B		---		---		B
	const DT_ACCESS_110							= 0b011; // ---		---		A|B		---		---		---
	const DT_ACCESS_001							= 0b100; // ---		A		A		A		A		A
	const DT_ACCESS_011							= 0b110; // ---		B		A|B		B		---		B
	const DT_ACCESS_101							= 0b101; // ---		---		A|B		B		---		---
	const DT_ACCESS_111							= 0b111; // ---		---		A|B		---		---		---

	const DT_ACCESS_DEFAULT = self::DT_ACCESS_001;
	const DT_ACCESS_NONE = self::DT_ACCESS_110;

	const DATA_BLOCK_0 = 0;
	const DATA_BLOCK_1 = 1;
	const DATA_BLOCK_2 = 2;

	/**
	 * Returns the authentication of a the required type
	 *
	 * @param int $type
	 * @return AuthenticationInterface
	 */
	public function getAuthentication(int $type): AuthenticationInterface;

	/**
	 * Returns an array of 4 bytes with the access control bits.
	 *
	 * @return array
	 */
	public function getAccessBits(): array;
}