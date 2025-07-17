<?php declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

use function PHPStan\Testing\assertType;


assertType(
	"Vojtechdobes\Conformance\Constraints\BooleanAll<mixed, 'a'>",
	new Vojtechdobes\Conformance\Constraints\BooleanAll([
		new Vojtechdobes\Conformance\Constraints\IsNonEmptyString(),
		new Vojtechdobes\Conformance\Constraints\Choice(['a']),
	]),
);

assertType(
	'Vojtechdobes\Conformance\Constraints\BooleanAll<mixed, *NEVER*>',
	new Vojtechdobes\Conformance\Constraints\BooleanAll([
		new Vojtechdobes\Conformance\Constraints\IsNonEmptyString(),
		new Vojtechdobes\Conformance\Constraints\IsNull(),
	]),
);
