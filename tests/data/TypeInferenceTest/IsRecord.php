<?php declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

use function PHPStan\Testing\assertType;


$isRecordClass = Vojtechdobes\Conformance\Constraints\IsRecord::class;

$isRecordArgs = [
	'requiredFields' => [
		'a' => new Vojtechdobes\Conformance\Constraints\IsNonEmptyString(),
		'b' => new Vojtechdobes\Conformance\Constraints\IsNonEmptyString(),
	],
];

assertType(
	'Vojtechdobes\Conformance\Constraints\IsRecord<array{a: Vojtechdobes\Conformance\Constraints\IsNonEmptyString, b: Vojtechdobes\Conformance\Constraints\IsNonEmptyString}, array{}, array{a: non-empty-string, b: non-empty-string}>',
	new $isRecordClass(...$isRecordArgs),
);

assertType(
	'Vojtechdobes\Conformance\Constraints\IsRecord<array{a: Vojtechdobes\Conformance\Constraints\IsNonEmptyString, b: Vojtechdobes\Conformance\Constraints\IsNonEmptyString}, array{c: Vojtechdobes\Conformance\Constraints\IsNonEmptyString, d: Vojtechdobes\Conformance\Constraints\IsNonEmptyString}, array{a: non-empty-string, b: non-empty-string, c?: non-empty-string, d?: non-empty-string}>',
	new Vojtechdobes\Conformance\Constraints\IsRecord(
		optionalFields: [
			'c' => new Vojtechdobes\Conformance\Constraints\IsNonEmptyString(),
			'd' => new Vojtechdobes\Conformance\Constraints\IsNonEmptyString(),
		],
		requiredFields: [
			'a' => new Vojtechdobes\Conformance\Constraints\IsNonEmptyString(),
			'b' => new Vojtechdobes\Conformance\Constraints\IsNonEmptyString(),
		],
	),
);
