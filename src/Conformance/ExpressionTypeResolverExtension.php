<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Conformance;

use PHPStan;
use PhpParser;
use Vojtechdobes;


final class ExpressionTypeResolverExtension implements PHPStan\Type\ExpressionTypeResolverExtension
{

	public function getType(
		PhpParser\Node\Expr $expr,
		PHPStan\Analyser\Scope $scope,
	): ?PHPStan\Type\Type
	{
		if (!$expr instanceof PhpParser\Node\Expr\New_) {
			return null;
		}

		if ($expr->class instanceof PhpParser\Node\Expr) {
			$constraintClassNames = $scope
				->getType($expr->class)
				->getClassStringObjectType()
				->getObjectClassNames();
		} elseif ($expr->class instanceof PhpParser\Node\Name) {
			$constraintClassNames = [
				$expr->class->name,
			];
		} elseif ($expr->class->name !== null) {
			$constraintClassNames = [
				$expr->class->name->toString(),
			];
		} else {
			$constraintClassNames = [];
		}

		$result = [];

		foreach ($constraintClassNames as $constraintClassName) {
			$resolver = match ($constraintClassName) {
				Vojtechdobes\Conformance\Constraints\BooleanAll::class => $this->resolveBooleanAll(...),
				Vojtechdobes\Conformance\Constraints\ForEachItem::class => $this->resolveForEachItem(...),
				Vojtechdobes\Conformance\Constraints\IsRecord::class => $this->resolveIsRecord(...),
				default => null,
			};

			if ($resolver === null) {
				continue;
			}

			$constraintType = $resolver($constraintClassName, $expr, $scope);

			if ($constraintType === null) {
				continue;
			}

			$result[] = $constraintType;
		}

		if ($result === []) {
			return null;
		}

		return PHPStan\Type\TypeCombinator::union(...$result);
	}



	private function resolveBooleanAll(
		string $className,
		PhpParser\Node\Expr\New_ $expr,
		PHPStan\Analyser\Scope $scope,
	): ?PHPStan\Type\Type
	{
		$parameters = $this->getConstraintConstructorParameters(
			$expr,
			$scope,
			$className,
			[
				'constraints',
			],
		);

		if ($parameters === null) {
			return null;
		}

		$arrays = $parameters['constraints']->getConstantArrays();

		if ($arrays === []) {
			return null;
		}

		return PHPStan\Type\TypeCombinator::union(
			...array_map(
				static fn ($array) => new PHPStan\Type\Generic\GenericObjectType($className, [
					$array
						->getFirstIterableValueType()
						->getTemplateType(Vojtechdobes\Conformance\Constraint::class, 'TPreValue'),
					PHPStan\Type\TypeCombinator::intersect(
						...array_map(
							static fn ($type) => $type->getTemplateType(Vojtechdobes\Conformance\Constraint::class, 'TPostValue'),
							$array->getValueTypes(),
						),
					),
				]),
				$arrays,
			),
		);
	}



	private function resolveForEachItem(
		string $className,
		PhpParser\Node\Expr\New_ $expr,
		PHPStan\Analyser\Scope $scope,
	): ?PHPStan\Type\Type
	{
		$parameters = $this->getConstraintConstructorParameters(
			$expr,
			$scope,
			$className,
			[
				'constraint',
			],
		);

		if ($parameters === null) {
			return null;
		}

		$constraint = $parameters['constraint'];

		if (new PHPStan\Type\ObjectType(Vojtechdobes\Conformance\Constraint::class)->isSuperTypeOf($constraint)->yes() === false) {
			return null;
		}

		return new PHPStan\Type\Generic\GenericObjectType($className, [
			$constraint->getTemplateType(Vojtechdobes\Conformance\Constraint::class, 'TPreValue'),
			$constraint->getTemplateType(Vojtechdobes\Conformance\Constraint::class, 'TPostValue'),
		]);
	}



	private function resolveIsRecord(
		string $className,
		PhpParser\Node\Expr\New_ $expr,
		PHPStan\Analyser\Scope $scope,
	): ?PHPStan\Type\Type
	{
		$parameters = $this->getConstraintConstructorParameters(
			$expr,
			$scope,
			$className,
			[
				'requiredFields',
				'optionalFields',
			],
		);

		if ($parameters === null) {
			return null;
		}

		$resultBuilders = [];

		foreach ($parameters['requiredFields']->getConstantArrays() as $array) {
			$constantArrayBuilder = PHPStan\Type\Constant\ConstantArrayTypeBuilder::createEmpty();

			foreach ($array->getKeyTypes() as $keyType) {
				$constantArrayBuilder->setOffsetValueType(
					$keyType,
					$array->getOffsetValueType($keyType)->getTemplateType(Vojtechdobes\Conformance\Constraint::class, 'TPostValue'),
				);
			}

			$resultBuilders[] = $constantArrayBuilder;
		}

		foreach ($parameters['optionalFields']->getConstantArrays() as $array) {
			foreach ($array->getKeyTypes() as $keyType) {
				foreach ($resultBuilders as $resultBuilder) {
					$resultBuilder->setOffsetValueType(
						$keyType,
						$array->getOffsetValueType($keyType)->getTemplateType(Vojtechdobes\Conformance\Constraint::class, 'TPostValue'),
						optional: true,
					);
				}
			}
		}

		return new PHPStan\Type\Generic\GenericObjectType($className, [
			$parameters['requiredFields'],
			$parameters['optionalFields'],
			PHPStan\Type\TypeCombinator::union(
				...array_map(
					static fn ($resultBuilder) => $resultBuilder->getArray(),
					$resultBuilders,
				),
			),
		]);
	}



	/**
	 * @template TKey of string
	 * @param non-empty-list<TKey> $parameters
	 * @return array<TKey, PHPStan\Type\Type>|null
	 */
	private function getConstraintConstructorParameters(
		PhpParser\Node\Expr\New_ $expr,
		PHPStan\Analyser\Scope $scope,
		string $className,
		array $parameters,
	): ?array
	{
		$args = [];

		$constructorReflection = $scope->getMethodReflection(
			new PHPStan\Type\ObjectType($className),
			'__construct',
		) ?? throw new PHPStan\ShouldNotHappenException();

		$nativeParameters = PHPStan\Reflection\ParametersAcceptorSelector::selectFromArgs(
			$scope,
			array_filter(
				$expr->args,
				static fn ($arg) => $arg instanceof PhpParser\Node\Arg,
			),
			$constructorReflection->getVariants(),
			$constructorReflection->getNamedArgumentsVariants(),
		)->getParameters();

		foreach ($expr->args as $i => $arg) {
			if ($arg instanceof PhpParser\Node\VariadicPlaceholder) {
				return null;
			}

			if ($arg->name !== null) {
				$args[$arg->name->toString()] = $scope->getType($arg->value);
			} elseif ($arg->unpack) {
				foreach ($scope->getType($arg->value)->getConstantArrays() as $argConstantArray) {
					foreach ($parameters as $parameter) {
						if ($argConstantArray->hasOffsetValueType(new PHPStan\Type\Constant\ConstantStringType($parameter))->yes()) {
							$args[$parameter] = $argConstantArray->getOffsetValueType(new PHPStan\Type\Constant\ConstantStringType($parameter));
						}
					}
				}
			} elseif (isset($nativeParameters[$i])) {
				$args[$nativeParameters[$i]->getName()] = $scope->getType($arg->value);
			}
		}

		$getNativeParameterType = static fn ($name) => PHPStan\Type\TypeCombinator::union(
			...array_map(
				static function ($variant) use ($name): PHPStan\Type\Type {
					$parameter = array_find(
						$variant->getParameters(),
						static fn ($parameter) => $parameter->getName() === $name,
					) ?? throw new PHPStan\ShouldNotHappenException();

					return $parameter->getDefaultValue() ?? $parameter->getType();
				},
				$constructorReflection->getVariants(),
			),
		);

		$result = [];

		foreach ($parameters as $parameter) {
			$result[$parameter] = $args[$parameter] ?? $getNativeParameterType($parameter);
		}

		return $result;
	}

}
