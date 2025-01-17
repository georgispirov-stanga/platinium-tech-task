<?php
declare(strict_types=1);

namespace App\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute]
class OrderTicketQuantity extends Constraint
{
	public string $message = 'Insufficient quantity availability.';
}