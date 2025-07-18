<?php declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

use function PHPStan\Testing\assertType;


assertType(
	"Vojtechdobes\Conformance\Constraints\ForEachItem<mixed, 'a'|'b'|'c'>",
	new Vojtechdobes\Conformance\Constraints\ForEachItem(
		new Vojtechdobes\Conformance\Constraints\Choice([
			'a',
			'b',
			'c',
		]),
	),
);
