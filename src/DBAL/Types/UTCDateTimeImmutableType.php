<?php
declare(strict_types=1);

namespace App\DBAL\Types;

use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\DateTimeImmutableType;
use Exception;

class UTCDateTimeImmutableType extends DateTimeImmutableType
{
	private static DateTimeZone $utc;

	private static DateTimeZone $serverDateTimezone;

	public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
	{
		if ($value instanceof DateTimeImmutable) {
			$value = $value->setTimezone(self::getUtc());
		}

		return parent::convertToDatabaseValue($value, $platform);
	}

	/**
	 * @throws ConversionException
	 * @throws Exception
	 */
	public function convertToPHPValue($value, AbstractPlatform $platform): ?DateTimeImmutable
	{
		if ($value === null || $value instanceof DateTimeImmutable) {
			return $value;
		}

		$dateTime = DateTimeImmutable::createFromFormat(
			$platform->getDateTimeFormatString(),
			$value,
			self::getUtc()
		);

		if ($dateTime !== false) {
			return $dateTime->setTimezone(self::getServerTimeZone());
		}

		try {
			return new DateTimeImmutable($value, self::getServerTimeZone());
		} catch (Exception $e) {
			throw ConversionException::conversionFailedFormat(
				$value,
				$this->getName(),
				$platform->getDateTimeFormatString(),
				$e
			);
		}
	}

	private static function getUtc(): DateTimeZone
	{
		return self::$utc ??= new DateTimeZone('UTC');
	}

	/**
	 * @throws Exception
	 */
	private static function getServerTimeZone(): DateTimeZone
	{
		return self::$serverDateTimezone ??= new DateTimeZone(date_default_timezone_get());
	}
}