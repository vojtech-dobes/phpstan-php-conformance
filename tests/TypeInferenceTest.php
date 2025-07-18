<?php declare(strict_types=1);

namespace Vojtechdobes\Tests;

use PHPStan;
use PHPUnit;


final class TypeInferenceTest extends PHPStan\Testing\TypeInferenceTestCase
{

	/**
	 * @return iterable<string, array<mixed>>
	 */
	public static function dataFileAsserts(): iterable
	{
		foreach (glob(__DIR__ . '/data/TypeInferenceTest/*.php') ?: [] as $file) {
			yield from self::gatherAssertTypes($file);
		}
	}



	#[PHPUnit\Framework\Attributes\DataProvider('dataFileAsserts')]
	public function testFileAsserts(
		string $assertType,
		string $file,
		mixed ...$args,
	): void
	{
		$this->assertFileAsserts($assertType, $file, ...$args);
	}



	public static function getAdditionalConfigFiles(): array
	{
		return [__DIR__ . '/../extension.neon'];
	}

}
