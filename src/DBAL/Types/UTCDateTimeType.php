<?php
declare(strict_types=1);

namespace App\DBAL\Types;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\DateTimeImmutableType;
use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\Deprecations\Deprecation;
use Exception;

class UTCDateTimeType extends DateTimeType
{
	private static DateTimeZone $utc;

	private static DateTimeZone $serverDateTimezone;

	public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
	{
		if ($value instanceof DateTime) {
			$value->setTimezone(self::getUtc());
		}

		return parent::convertToDatabaseValue($value, $platform);
	}

	/**
	 * @throws ConversionException
	 * @throws Exception
	 */
	public function convertToPHPValue($value, AbstractPlatform $platform): ?DateTimeInterface
	{
		if ($value instanceof DateTimeImmutable) {
			Deprecation::triggerIfCalledFromOutside(
				'doctrine/dbal',
				'https://github.com/doctrine/dbal/pull/6017',
				'Passing an instance of %s is deprecated, use %s::%s() instead.',
				get_class($value),
				DateTimeImmutableType::class,
				__FUNCTION__,
			);
		}

		if ($value === null || $value instanceof DateTimeInterface) {
			return $value;
		}

		$dateTime = DateTime::createFromFormat($platform->getDateTimeFormatString(), $value, self::getUtc());

		if ($dateTime !== false) {
			return $dateTime->setTimezone(self::getServerTimeZone());
		}

		try {
			return new DateTime($value, self::getServerTimeZone());
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