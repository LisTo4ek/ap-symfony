<?php

declare(strict_types=1);

namespace App\Domain\Validation\ArgumentResolver;

use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use function implode;

abstract class AbstractDtoResolver implements ValueResolverInterface
{
    public function processErrors(ConstraintViolationListInterface $errors): void
    {
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = $error->getMessage();
            }
            throw new BadRequestHttpException(implode(', ', $messages));
        }
    }
}
